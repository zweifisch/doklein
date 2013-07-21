<?php

require 'vendor/autoload.php';

require 'vendor/brainsware/php-markdown-extra-extended/markdown_extended.php';

$app = new zf\App;

$app->helper->register('get_path', function($path_segments){
	return $this->config->folder.DIRECTORY_SEPARATOR.
		implode(DIRECTORY_SEPARATOR, $path_segments);
});

$app->helper->register('render_md', function(){
	$path = $this->get_path(func_get_args()) . $this->config->extension;
	if(is_readable($path)){
		$content = MarkdownExtended(file_get_contents($path));
		$this->render('template',['content'=>$content]);
	}else{
		$this->send(404);
	}
});

$app->docs = function(){
	$walk_dir = function($dir, &$ret) use (&$walk_dir){
		$dh = opendir($dir);
		while($file = readdir($dh)){
			if(in_array($file, ['.', '..'])) continue;
			is_dir($dir.DIRECTORY_SEPARATOR.$file)
				? $walk_dir($dir.DIRECTORY_SEPARATOR.$file, $ret[$file])
				: $ret[] = basename($file, $this->config->extension);
		}
	};
	$walk_dir($this->config->folder, $ret);
	return $ret;
};

$app->param('folder', function($value){
	$folders = array_keys($this->docs);
	$value = preg_quote($value);
	foreach($folders as $folder){
		if(preg_match("/\d+_$value/i", $folder)) return $folder;
	}
});

$app->param('article', function($value){
	if($this->params->folder){
		$value = preg_quote($value);
		$articles = $this->docs[$this->params->folder];
		foreach($articles as $article){
			if(preg_match("/\d+_$value/i", $article)) return $article;
		}
	}
});

$app->get('/', function(){
	$this->render_md('index');
});

$app->get('/:folder/:article?', function(){
	if($this->params->folder){
		$article = $this->params->article ? $this->params->article : 'index';
		$this->render_md($this->params->folder, $article);
	}else{
		$this->send(404);
	}
});

$app->run();
