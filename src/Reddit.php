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


	private function setBaseUrl($sort='new')
	{
		$this->base_url = 'https://gateway.reddit.com/desktopapi/v1/subreddits/'.$this->subreddit.'?rtj=only&redditWebClient=web2x&app=web2x-client-production&allow_over18=1&include=identity&sort='.$sort.'&layout=compact';
	}


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


	public function getPosts($sub, $sort='new')
	{
		$this->subreddit = $sub;
		$this->setBaseUrl($sort);
		$response = $this->client->request('GET', $this->base_url);
		$body = json_decode($response->getBody()->getContents(), true);
		return array('posts'=>$body['posts'], 'token'=>$body['token'], 'dist'=>$body['dist'], 'sort'=>$body['listingSort'], 'sub'=>$sub);
	}


	public function nextPosts($sub, $token, $dist=25, $sort='new')
	{
		$this->rec_url = 'https://gateway.reddit.com/desktopapi/v1/subreddits/'.$sub.'?rtj=only&redditWebClient=web2x&app=web2x-client-production&allow_over18=1&include=identity&after='.$token.'&dist='.$dist.'&layout=compact&sort='.$sort.'&geo_filter=';
		$response = $this->client->request('GET', $this->rec_url);
		$body = json_decode($response->getBody()->getContents(), true);
		return array('posts'=>$body['posts'], 'token'=>$body['token'], 'dist'=>$body['dist'], 'sort'=>$body['listingSort'], 'sub'=>$sub);
	}


	public function viewPost($token)
	{
		$this->rec_url = 'https://gateway.reddit.com/desktopapi/v1/postcomments/'.$token.'?rtj=only&emotes_as_images=true&redditWebClient=web2x&app=web2x-client-production&profile_img=true&allow_over18=1&include=identity&include_categories=true';
		$response = $this->client->request('GET', $this->rec_url);
		$body = json_decode($response->getBody()->getContents(), true);
		$first_key_post = array_key_first($body['posts']);
		return array('post'=>$body['posts'][$first_key_post], 'comments'=>$body['comments']);
	}


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


	public function downloadMediasBySub($sub)
	{
		$posts = $this->getPosts($sub);
		$medias = $this->parseMediaByPosts($posts['posts']);
		$this->downloadMedias($medias);
	}

	public function downloadMedias($arr)
	{
		foreach ($arr as $media) {
			$this->downloadMedia($media);
		}
	}

	public function downloadMedia($arr)
	{
		if(isset($arr['status']) && $arr['status'] === "success")
		{
			if(isset($arr['type']) && isset($arr['data'])) {
				switch (true) {
					case ($arr['type']==="single"):
						$this->downloadSingleFile($arr['data']);
						break;

					case ($arr['type']==="gallery"):
						$this->downloadGalleryFiles($arr['data']);
						break;
					
					default:
						break;
				}
			}
		}
	}

	private function downloadSingleFile($content)
	{
		//$location = $this->checkDirectory();
		$filename = basename($content);

		// $resource = fopen('php://output', 'w+');
		$file = file_get_contents($content);

		// header('Content-Description: File Transfer');
		// header('Content-Type: application/octet-stream');
		// header('Content-Disposition: attachment; filename="'.basename($content).'"');
		// header('Expires: 0');
		// header('Cache-Control: must-revalidate');
		// header('Pragma: public');
		// flush(); // Flush system output buffer

		// while (!feof($resource)) {
		// 	echo fread($resource, $file);
		// 	flush();
		// }


		$zipname = 'logs.zip';
		$zip = new ZipArchive;
		$zip->open($zipname, ZipArchive::CREATE);
		$zip->addFile($file);
		$zip->close();





		// $res = $this->client->get($content, [
		//     'verify' => false,
		//     'sink' => $resource,
		// ]);

		//fclose($resource);


	}

	private function downloadGalleryFiles($arr)
	{
		foreach ($arr as $value) {
			$this->downloadSingleFile($value);
		}
	}


	public function parseMedia($media)
	{
		switch (true) {
			case ($media['type']==="image" || $media['type']==="gifvideo"):
				$data = array('status'=>'success', 'type'=>'single', 'data'=>$media['content']);
				break;

			case ($media['type']=="gallery"):
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



	public function getPostsCount($sub)
	{
		$this->subreddit = $sub;
		$this->setBaseUrl();
		$postCount = $this->init_fetch_post_count($this->base_url);
		return $postCount;
	}


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

	private function checkDirectory()
	{
		$location = "downloads/";
		if(!file_exists($location)) {
			mkdir($location, 0777);
		}
		return $location;
	}


}