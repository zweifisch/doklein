<?php

require 'vendor/autoload.php';

require 'vendor/brainsware/php-markdown-extra-extended/markdown_extended.php';

$app = new zf\App;


$app->helper->register('path_constructor', function($root_path){
	return function($path_segments) use($root_path){
		return $root_path.DIRECTORY_SEPARATOR.
			implode(DIRECTORY_SEPARATOR, $path_segments);
	};
});

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

$app->helper->register('render_md_as_str', Function(){
	$path = $this->get_path(func_get_args()) . $this->config->extension;
	if(is_readable($path)){
		$content = MarkdownExtended(file_get_contents($path));
		return $this->renderAsString('template',['content'=>$content]);
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

$app->cmd('export <path>', function(){
	$get_target_path = $this->path_constructor($this->params->path);
	$this->log('exporting to %s', $this->params->path);
	$to_be_process = [];
	foreach($this->docs as $folder=>$articles){
		if(is_int($folder)){
			$this->log('processing %s', $articles);
			$to_be_process[] = $articles;
		}else{
			foreach($articles as $article){
				$to_be_process[] = $folder.DIRECTORY_SEPARATOR.$article;
			}
		}
	}
	foreach($to_be_process as $article)
	{
		$this->log('processing %s%s', $article, $this->config->extension);
		$path = $get_target_path([$article.$this->config->export_extension]);
		is_dir(dirname($path)) or mkdir(dirname($path), 0755, true);
		file_put_contents($path, $this->render_md_as_str($article));
	}
	
});

$app->run();
