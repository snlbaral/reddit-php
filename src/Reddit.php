<?php

namespace Snlbaral\Reddit;

use GuzzleHttp\Client as GuzzleHttpClient;


class Reddit
{
	private $client;
	private $base_url;
	private $rec_url;
	private $subreddit;
	private $post_count;



	function __construct()
	{
		$this->client = new GuzzleHttpClient();
		$this->post_count = 0;
	}



	/**
	 * Sets Base URL
	 *
	 * @param string $sort sorting method
	 * @return NULL
	 */
	private function setBaseUrl($sort='new')
	{
		$this->base_url = 'https://gateway.reddit.com/desktopapi/v1/subreddits/'.$this->subreddit.'?rtj=only&redditWebClient=web2x&app=web2x-client-production&allow_over18=1&include=identity&sort='.$sort.'&layout=compact';
	}



	/**
	 * Gets Information about Subreedit
	 *
	 * @param string $sub Subreddit name
	 * @return array information about subreddit
	 */
	public function getInfo($sub)
	{
		$this->subreddit = $sub;
		$this->setBaseUrl();
		$response = $this->client->request('GET', $this->base_url);
		$body = json_decode($response->getBody()->getContents(), true);
		$first_key_basic = array_key_first($body['subredditAboutInfo']);
		$first_key_extra = array_key_first($body['subreddits']);
		return array('basic_info'=>$body['subredditAboutInfo'][$first_key_basic], 'extra_info'=>$body['subreddits'][$first_key_extra]);	
	}



	/**
	 * Fetch Posts of Subreddit accroding to token/postId
	 *
	 * @param string $sub Subreddit name
	 * @param string $token token/postId, if not provided, it will fetch first page/thread results
	 * @param int $dist Optional
	 * @param string(optional) $sort post sorting i.e. new, hot, top
	 * @return array Array including posts, token, dist, sorting method and subreddit name
	 */
	public function getPosts($sub, $token=false, $dist=25, $sort='new')
	{
		if($token===false) {
			$this->subreddit = $sub;
			$this->setBaseUrl($sort);
			$response = $this->client->request('GET', $this->base_url);
			$body = json_decode($response->getBody()->getContents(), true);
		} else {
			$this->rec_url = 'https://gateway.reddit.com/desktopapi/v1/subreddits/'.$sub.'?rtj=only&redditWebClient=web2x&app=web2x-client-production&allow_over18=1&include=identity&after='.$token.'&dist='.$dist.'&layout=compact&sort='.$sort.'&geo_filter=';
			$response = $this->client->request('GET', $this->rec_url);
			$body = json_decode($response->getBody()->getContents(), true);			
		}
		return array('posts'=>$body['posts'], 'token'=>$body['token'], 'dist'=>$body['dist'], 'sort'=>$body['listingSort'], 'sub'=>$sub);
	}


	// /**
	//  * Fetch Posts of Subreddit of next page/thread accroding to token/postId
	//  *
	//  * @param string $sub Subreddit name
	//  * @param string $token token/postId to navigate to next page/thread //returned from getPosts(), nextPosts() method
	//  * @param int $dist Optional
	//  * @param string(optional) $sort post sorting i.e. new, hot, top
	//  * @return array Array including posts, token, dist, sorting method and subreddit name
	//  */
	// public function nextPosts($sub, $token, $dist=25, $sort='new')
	// {
	// 	$this->rec_url = 'https://gateway.reddit.com/desktopapi/v1/subreddits/'.$sub.'?rtj=only&redditWebClient=web2x&app=web2x-client-production&allow_over18=1&include=identity&after='.$token.'&dist='.$dist.'&layout=compact&sort='.$sort.'&geo_filter=';
	// 	$response = $this->client->request('GET', $this->rec_url);
	// 	$body = json_decode($response->getBody()->getContents(), true);
	// 	return array('posts'=>$body['posts'], 'token'=>$body['token'], 'dist'=>$body['dist'], 'sort'=>$body['listingSort'], 'sub'=>$sub);
	// }



	/**
	 * Fetch/Get Post & Comments of Single Post based on token/postId
	 *
	 * @param string $token postId or token
	 * @return array Array of post & comments
	 */
	public function viewPost($token)
	{
		$this->rec_url = 'https://gateway.reddit.com/desktopapi/v1/postcomments/'.$token.'?rtj=only&emotes_as_images=true&redditWebClient=web2x&app=web2x-client-production&profile_img=true&allow_over18=1&include=identity&include_categories=true';
		$response = $this->client->request('GET', $this->rec_url);
		$body = json_decode($response->getBody()->getContents(), true);
		$first_key_post = array_key_first($body['posts']);
		return array('post'=>$body['posts'][$first_key_post], 'comments'=>$body['comments']);
	}



	/**
	 * Parse Media Files from Array of Posts
	 *
	 * @param array $posts Array of Posts
	 * @return array Array of parse status, post type & media direct download link
	 */
	public function parseMediaByPosts($posts)
	{
		$data = array();
		foreach ($posts as $key => $post) {
			if(isset($post['media']['type'])) {
				$arr = $this->parseMedia($post['media']);
				$data[$key] = $arr;
			} else {
				$data[$key] = array('status'=>'failed', 'data'=>'Not a type media');
			}
		}
		return $data;
	}



	/**
	 * Download Media Files of subreddit
	 *
	 * @param string $sub Subreddit name
	 * @param string(optional) $token postId/token, if empty it will download first page media and return token to use next time and download next page media files
	 * @param int $dist Optional
	 * @param string $sort sorting method Optional
	 * @param string $dir Download Directory/Location
	 * @return array Array including postId/token, dist, sorting method and subreddit name
	 */
	public function downloadMediasBySub($sub, $token=false, $dist=25, $sort='new', $dir="downloads")
	{
		if($token===false) {
			$posts = $this->getPosts($sub);
			$this->downloadMediasByPosts($posts['posts'], $dir);
		} else {
			$posts = $this->getPosts($sub, $token, $dist, $sort);
			$this->downloadMediasByPosts($posts['posts'], $dir);
		}
		return array('token'=>$posts['token'], 'dist'=>$posts['dist'], 'sort'=>$posts['sort'], 'sub'=>$sub);
	}



	/**
	 * Download Media Files from Posts Array
	 *
	 * @param array $posts Array of posts i.e. returned from getPosts() or nextPosts()
	 * @param string $dir directory/lcation to save downloaded files
	 * @return NULL
	 */
	public function downloadMediasByPosts($posts, $dir="downloads")
	{
		$medias = $this->parseMediaByPosts($posts);
		$request = array();
		foreach ($medias as $media) {
			$res = $this->createRequest($media);
			if($res) {
				switch (true) {
					case (is_array($res)):
						foreach ($res as $key => $value) {
							$request[] = $value;
						}
						break;
					
					default:
						$request[] = $res;
						break;
				}
			}
		}
		$dir = $this->checkDirectory($dir);
		$this->downloadFiles($request, $dir);
	}



	/**
	 * Create URLs to download files in async
	 *
	 * @param array $arr Array of each media returned by parseMediaByPosts()
	 * @return string or array of direct download links
	 */
	private function createRequest($arr)
	{
		if(isset($arr['status']) && $arr['status'] === "success")
		{
			if(isset($arr['type']) && isset($arr['data'])) {
				switch (true) {
					case ($arr['type']==="single"):
						$data = $arr['data'];
						return $data;
						break;

					case ($arr['type']==="gallery"):
						$data = array();
						foreach ($arr['data'] as $value) {
							$data[] = $value;
						}
						return $data;
						break;
					
					default:
						break;
				}
			}
		}
		return false;
	}



	/**
	 * Validate Filename and make changesfilename if needed to avoid overwrite i.e. DASH_1080.mp4 to random name
	 *
	 * @param string $url download link url
	 * @return string download link url with changes in filename if needed
	 */
	private function validateFileName($url)
	{
		if(strpos($url, "?")!==false) {
			$filter = explode("?", $url);
			$request = $filter[0];
		} elseif (strpos($url, "DASH_")!==false) {
			$filename = basename($url);
			$extension = pathinfo($filename, PATHINFO_EXTENSION);
			$new_filename = time().".".$extension;
			$request = str_replace($filename, $new_filename, $url);
		} else {
			$request = $url;
		}
		return $request;
	}



	/**
	 * Download Files in async
	 *
	 * @param array $uris Array of urls to download files
	 * @param string $dir directory/location to save downloaded files
	 * @param boolean $overwrite
	 * @return NULL | prints issue if failes to download
	 */
	private function downloadFiles($uris, $dir, $overwrite=true) {
	    $client = $this->client;
	    $promises = (function () use ($client, $uris, $dir, $overwrite) {
	        foreach ($uris as $uri) {
	        	$filename  = $this->validateFileName($uri);
	            $loc = $dir . DIRECTORY_SEPARATOR . basename($filename);
	            if ($overwrite && file_exists($loc)) unlink($loc);
	            yield $client->requestAsync('GET', $uri, ['sink' => $loc]);
	        }
	    })();
	    (new \GuzzleHttp\Promise\EachPromise(
	        $promises, [
	        'concurrency' => 10,
	        'fulfilled'   => function (\Psr\Http\Message\ResponseInterface $response) {
	        },
	        'rejected'    => function ($reason, $index) {
	            echo 'ERROR => ' . strtok($reason->getMessage(), "\n") . PHP_EOL;
	        },
	    ])
	    )->promise()->wait();
	}



	/**
	 * Parse Media Files of a media array i.e. $post['media'] in loop of $posts
	 *
	 * @param array $media media array
	 * @return array Array including parse status, type, direct download link
	 */
	public function parseMedia($media)
	{
		switch (true) {
			case ($media['type']==="image" || $media['type']==="gifvideo"):
				$data = array('status'=>'success', 'type'=>'single', 'data'=>$media['content']);
				break;

			case ($media['type']==="gallery"):
				$gallery_arr = array();
				foreach ($media['mediaMetadata'] as $gallery_key => $gallery) {
					$ext = str_replace("image/", ".", $gallery['m']);
					$content = "https://i.redd.it/".$gallery_key.$ext;
					$gallery_arr[$gallery_key] = $content;
				}
				$data = array('status'=>'success', 'type'=>'gallery', 'data'=>$gallery_arr);
				break;

			case ($media['type']==="video"):
				$filter = explode("DASH", $media['scrubberThumbSource']);
				$video = $filter[0]."DASH_".$media['height'].".mp4";
				$data = array('status'=>'success', 'type'=>'single', 'data'=>$video);
				break;
			
			default:
				$data = array('status'=>'failed', 'data'=>'Not a type media');
				break;
		}
		return $data;
	}


	/**
	 * User Overview Page
	 *
	 * @param string $username username
	 * @param string $token token to navigate next page/thread. if not given will fetch first page/thread result
	 * @param int $dist
	 * @param string $sort
	 * @return array of user's posts, comments, next page token etc
	 */
	public function userOverview($username, $token=false, $dist=25, $sort='new')
	{
		if($token===false) {
			$this->rec_url = 'https://gateway.reddit.com/desktopapi/v1/user/'.$username.'/conversations?rtj=only&redditWebClient=web2x&app=web2x-client-production&allow_over18=1&include=identity&dist='.$dist.'&sort='.$sort;
		} else {
			$this->rec_url = 'https://gateway.reddit.com/desktopapi/v1/user/'.$username.'/conversations?rtj=only&redditWebClient=web2x&app=web2x-client-production&allow_over18=1&include=identity&after='.$token.'&dist='.$dist.'&sort='.$sort;
		}
		$response = $this->client->request('GET', $this->rec_url);
		$body = json_decode($response->getBody()->getContents(), true);
		return array('posts'=>$body['posts'], 'comments'=>$body['comments'], 'token'=>$body['token'], 'dist'=>$body['dist'], 'sort'=>$sort, 'username'=>$username);
	}



	/**
	 * User's Post Page
	 *
	 * @param string $username username
	 * @param string $token token to navigate next page/thread. if not given will fetch first page/thread result
	 * @param int $dist
	 * @param string $sort
	 * @return array of user's posts, next page token etc
	 */
	public function userPosts($username, $token=false, $dist=25, $sort='new')
	{
		if($token===false) {
			$this->rec_url = 'https://gateway.reddit.com/desktopapi/v1/user/'.$username.'/posts?rtj=only&allow_quarantined=true&redditWebClient=web2x&app=web2x-client-production&allow_over18=1&include=identity&dist='.$dist.'&layout=classic&sort='.$sort;
		} else {
			$this->rec_url = 'https://gateway.reddit.com/desktopapi/v1/user/'.$username.'/posts?rtj=only&allow_quarantined=true&redditWebClient=web2x&app=web2x-client-production&allow_over18=1&include=identity&after='.$token.'&dist='.$dist.'&layout=classic&sort='.$sort;
		}
		$response = $this->client->request('GET', $this->rec_url);
		$body = json_decode($response->getBody()->getContents(), true);
		return array('posts'=>$body['posts'], 'token'=>$body['token'], 'dist'=>$body['dist'], 'sort'=>$sort, 'username'=>$username);
	}



	/**
	 * User's Comments Page
	 *
	 * @param string $username username
	 * @param string $token token to navigate next page/thread. if not given will fetch first page/thread result
	 * @param int $dist
	 * @param string $sort
	 * @return array of user's comments, next page token etc
	 */
	public function userComments($username, $token=false, $dist=25, $sort='new')
	{
		if($token===false) {
			$this->rec_url = 'https://gateway.reddit.com/desktopapi/v1/user/'.$username.'/comments?rtj=only&emotes_as_images=true&allow_quarantined=true&redditWebClient=web2x&app=web2x-client-production&profile_img=true&allow_over18=1&include=identity&dist='.$dist.'&sort='.$sort;
		} else {
			$this->rec_url = 'https://gateway.reddit.com/desktopapi/v1/user/'.$username.'/comments?rtj=only&emotes_as_images=true&allow_quarantined=true&redditWebClient=web2x&app=web2x-client-production&profile_img=true&allow_over18=1&include=identity&after='.$token.'&dist='.$dist.'&sort='.$sort;
		}
		$response = $this->client->request('GET', $this->rec_url);
		$body = json_decode($response->getBody()->getContents(), true);
		return array('comments'=>$body['comments'], 'token'=>$body['token'], 'dist'=>$body['dist'], 'sort'=>$sort, 'username'=>$username);
	}



	/**
	 * Count Total Posts Of SubReddit | Times Out PHP MAX Execution Time | Avoid Using For Now
	 *
	 * @param string $sub subreddit name
	 * @return int total posts count
	 */
	public function getPostsCount($sub)
	{
		$this->subreddit = $sub;
		$this->setBaseUrl();
		$postCount = $this->init_fetch_post_count($this->base_url);
		return $postCount;
	}



	/**
	 * Count Total Posts Init | Loops Until The End Of Subreddit
	 *
	 * @param string $url Reddit API URL
	 * @return int total posts
	 */
	private function init_fetch_post_count($url)
	{
		$fetch = $this->fetch_posts_count($url);
		if($fetch) {
			$fetch = json_decode($fetch, true);
			$this->post_count += $fetch['count'];
			$this->rec_url = 'https://gateway.reddit.com/desktopapi/v1/subreddits/'.$this->subreddit.'?rtj=only&redditWebClient=web2x&app=web2x-client-production&allow_over18=1&include=identity&after='.$fetch["token"].'&dist='.$fetch["dist"].'&layout=compact&sort='.$fetch["sort"].'&geo_filter=';
			$this->init_fetch_post_count($this->rec_url);
		}
		return $this->post_count;
	}



	/**
	 * Count Total Posts Worker | Calls API
	 *
	 * @param string $url Reddit API URL
	 * @return array including total posts in the page of supplied url, token, dist & sort to loop next page
	 */
	private function fetch_posts_count($url)
	{
		$response = $this->client->request('GET', $url);
		$body = json_decode($response->getBody()->getContents());
		$init_posts_count = count((array)$body->posts);
		$loopCount = 1;
		if($init_posts_count===0) {
			return false;
		} else {
			return json_encode(array('count'=>$init_posts_count, 'token'=>$body->token, 'dist'=>$body->dist, 'sort'=>$body->listingSort));
		}
	}



	/**
	 * Check & Create Directory
	 *
	 * @param string $dir Directory Name
	 * @return string Directory Name
	 */
	private function checkDirectory($dir)
	{
		$location = $dir."/";
		if(!file_exists($location)) {
			mkdir($location, 0777);
		}
		return $dir;
	}


}