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
	$args = func_get_args();
	$asString = is_bool($args[func_num_args() - 1]) ? array_pop($args) : false;
	$path = $this->get_path($args) . $this->config->extension;
	if(is_readable($path)){
		$content = MarkdownExtended(file_get_contents($path));
		if($asString){
			return $this->renderAsString('template',['content'=>$content]);
		}else{
			$this->render('template',['content'=>$content, 'docs'=>$this->docs]);
		}
	}
});

$app->helper->register('strip_num', function($input){
	return is_numeric(strstr($input, '_', true)) ? substr(strstr($input, '_'), 1) : $input;
});

$app->helper->register('as_path', function($path){
	return strtolower($this->helper->strip_num($path));
});

$app->helper->register('as_title', function($path){
	return str_replace('_', ' ', $this->helper->strip_num($path));
});

$app->helper->register('is_current_folder', function($folder){
	if(empty($this->params->folder)) return false;
	return $this->as_path($this->params->folder) == $folder;
});

$app->helper->register('is_current_article', function($folder, $article){
	if(empty($this->params->article)) return false;
	return $this->as_path($this->params->article) == $article && $this->helper->is_current_folder($folder);
});

$app->docs = function(){
	$walk_dir = function($dir, &$ret) use (&$walk_dir){
		$dh = opendir($dir);
		while($file = readdir($dh)){
			if(in_array($file, ['.', '..', 'index'.$this->config->extension])) continue;
			is_dir($dir.DIRECTORY_SEPARATOR.$file)
				? $walk_dir($dir.DIRECTORY_SEPARATOR.$file, $ret[$file])
				: $ret[] = basename($file, $this->config->extension);
		}
		asort($ret);
	};
	$walk_dir($this->config->folder, $ret);
	ksort($ret);
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
	$this->render_md('index') or $this->send(404);
});

$app->get('/:folder/:article?', function(){
	if($this->params->folder){
		$this->render_md($this->params->folder, $this->params->article);
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
		file_put_contents($path, $this->render_md($article, true));
	}
	
});

$app->run();
