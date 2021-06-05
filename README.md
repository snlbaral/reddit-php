# reddit-php

[![Latest Stable Version](https://poser.pugx.org/snlbaral/reddit-php/v)](//packagist.org/packages/snlbaral/reddit-php) [![Total Downloads](https://poser.pugx.org/snlbaral/reddit-php/downloads)](//packagist.org/packages/snlbaral/reddit-php) [![Latest Unstable Version](https://poser.pugx.org/snlbaral/reddit-php/v/unstable)](//packagist.org/packages/snlbaral/reddit-php) [![License](https://poser.pugx.org/snlbaral/reddit-php/license)](//packagist.org/packages/snlbaral/reddit-php)


This is an open source library that allows PHP applications to interact programmatically with the Reddit's API without the requirement of oauth.


Requirements
------------

Using this library for PHP requires the following:

* [Composer] or a manual install of the dependencies mentioned in
  `composer.json`.


Installation
------------

The recommended way to install it PHP is to install it using

```sh
composer require snlbaral/reddit-php
```



Usages
----------

**Init**
```php
require 'vendor/autoload.php';
use Snlbaral\Reddit\Reddit;

$reddit = new Reddit();
```


**Fetch Posts Of Subreddit**
```php
// getPosts() @params $subreddit_name, $token (optional), $dist (optional), $sort (optional)
$posts = $reddit->getPosts('subreddit_name');
// returns array of posts, next page token, dist, sorting method, subreddit name
// print_r($posts);

// To Get next page/thread posts use following:
$next_page = $reddit->getPosts('subreddit_name', $posts['token']);
// returns array of next page/thread posts, next page token, dist, sorting method, subreddit name
```


**Fetch Subreddit Info**
```php
$subreddit_info = $reddit->getInfo('subreddit_name');
```


**View/Get Single Post Info/Detail Page**
```php
$postId = 'postId or token';
$post = $reddit->viewPost($postId);
```


**Fetch User Overview Page**
```php
// userOverview() @params $username, $token (optional), $dist (optional), $sort (optional)
$overview = $reddit->userOverview('username');
// returns array of user's posts, comments, next page token, dist, sorting method, username

// To Get next page/thread posts and comments of user, use following
$next_page_overview = $reddit->userOverview('username', $overview['token']);
```


**Fetch User Posts Page**
```php
// userPosts() @params $username, $token (optional), $dist (optional), $sort (optional)
$user_posts = $reddit->userPosts('username');
// returns array of user's posts next page token, dist, sorting method, username

// To Get next page/thread posts of user, use following
$next_page_user_posts = $reddit->userPosts('username', $user_posts['token']);
```


**Fetch User Comments Page**
```php
// userComments() @params $username, $token (optional), $dist (optional), $sort (optional)
$comments = $reddit->userComments('username');
// returns array of user's comments, next page token, dist, sorting method, username

// To Get next page/thread comments of user, use following
$next_page_comments = $reddit->userComments('username', $comments['token']);
```


**Download Media Files From A Subreddit**
```php
// downloadMediasBySub() @params $subreddit_name, $token (optional), $dist (optional), $sort (optional), $dir (optional)
$downloads = $reddit->downloadMediasBySub('subreddit_name');
// Downloads All Media Files from first page of subreddit using async, saves in $dir location
// returns array of next page token, dist, sorting method and subreddit name

// To download next page/thread media files, use following
$next_page_downloads = $reddit->downloadMediasBySub('subreddit_name', $downloads['token']);
// Downloads All Media Files from next page of subreddit using async, saves in $dir location
// returns array of next page token, dist, sorting method and subreddit name
```


**Example.php**

```php
try {
	$downloads = $reddit->downloadMediasBySub('subreddit_name', false, 25, 'new', 'mydownloads');
	print_r($downloads);
	//echo $downloads['token'];
} catch (Exception $e) {

	if($e->getResponse()) {
		$response = $e->getResponse();
		$responseBodyAsString = $response->getBody()->getContents();
		var_dump($responseBodyAsString);
	} else {
		var_dump($e);
	}

}
```


License
-------

This library for PHP is licensed under the <a href="https://opensource.org/licenses/BSD-3-Clause">3-Clause
BSD License</a>

Credits
-------

This library for PHP is developed and maintained by Sunil Baral.
