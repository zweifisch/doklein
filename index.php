<?php

require 'vendor/autoload.php';
require 'vendor/brainsware/php-markdown-extra-extended/markdown_extended.php';

$app = new zf\App;

$app->helper->register('path_constructor', function($root_path){
	return function($path_segments) use($root_path){
		return $root_path.DIRECTORY_SEPARATOR.
			implode(DIRECTORY_SEPARATOR, array_filter($path_segments, function($x){return !empty($x);}));
	};
});

$app->helper->register('get_path', function($path_segments){
	return $this->config->folder.DIRECTORY_SEPARATOR.
		implode(DIRECTORY_SEPARATOR, $path_segments);
});

$app->helper->register('render_md', function($path, $vars=[], $return=false){
	$path = $this->get_path(explode('/', $path)) . $this->config->extension;
	if(is_readable($path)){
		$content = MarkdownExtended(file_get_contents($path));
		$vars['content'] = $content;
		$vars['docs'] = $this->docs;
		if($return){
			return $this->renderAsString('template', $vars);
		}else{
			$this->render('template', $vars);
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

$app->docs = function(){
	$walk_dir = function($dir, &$ret) use (&$walk_dir){
		$dh = opendir($dir);
		while($file = readdir($dh)){
			if(in_array($file, ['.', '..', 'index'.$this->config->extension])) continue;
			is_dir($dir.DIRECTORY_SEPARATOR.$file)
				? $walk_dir($dir.DIRECTORY_SEPARATOR.$file, $ret[$file])
				: $ret[$file] = basename($file, $this->config->extension);
		}
		asort($ret);
	};
	$walk_dir($this->config->folder, $ret);
	ksort($ret);
	return $ret;
};

$app->links = function(){
	return isset($this->config->links) ? $this->config->links : [];
};

$app->param('folder', function($value){
	$folders = array_keys($this->docs);
	$value = preg_quote($value);
	foreach($folders as $folder){
		if(preg_match("/\d+_$value/i", $folder)) return $folder;
	}
});

$app->param('article', function($value){
	$value = strstr($value, '.html', true);
	$articles = empty($this->params->folder) ? $this->docs : $this->docs[$this->params->folder];
	$value = preg_quote($value);
	foreach($articles as $article){
		if(is_string($article) && preg_match("/\d+_$value/i", $article)) return $article;
	}
});

$app->get('/', function(){
	$this->render_md('index',[
		'current_folder'=>'',
		'current_article'=>'',
		'root'=>'']) or $this->send(404);
});

$app->get('/:article', function(){
	$this->render_md($this->params->article, [
		'current_folder'=>'',
		'current_article'=>$this->params->article,
		'root'=>'']) or $this->send(404);
});

$app->get('/:folder/:article', function(){
	if($this->params->folder){
		$this->render_md($this->params->folder.'/'.$this->params->article,[
			'current_folder' => $this->params->folder,
			'current_article' => $this->params->article,
			'root' => '../',
		]);
	}else{
		$this->send(404);
	}
});

$app->cmd('export <path>', function(){
	$get_target_path = $this->path_constructor($this->params->path);
	$this->log('exporting to %s', $this->params->path);
	$to_be_process = [];
	foreach($this->docs as $folder=>$articles){
		if(is_string($articles)){
			$to_be_process[] = ['', $articles];
		}else{
			foreach($articles as $article){
				$to_be_process[] = [$folder, $article];
			}
		}
	}
	$to_be_process[] = ['', 'index'];
	foreach($to_be_process as $item){
		list($folder,$article) = $item;
		if($folder){ 
			$md_path = "$folder/$article";
			$path = $get_target_path([$this->as_path($folder),
				$this->as_path($article).$this->config->export_extension]);
		}else{
			$md_path = $article;
			$path = $get_target_path([$this->as_path($article).$this->config->export_extension]);
		}
		$this->log('processing %s%s', $md_path, $this->config->extension);
		is_dir(dirname($path)) or mkdir(dirname($path), 0755, true);
		file_put_contents($path, $this->render_md($md_path, [
			'current_folder' => $folder,
			'current_article' => $article == 'index' ? '':$article,
			'root' => $folder ? '../' : '',
		], true));
	}
	if($this->params->{'copy-assets'}){
		$files = ['github.css', 'highlight.js', 'style.css'];
		is_dir($get_target_path(['assets'])) or mkdir($get_target_path(['assets']), 0755, true);
		foreach($files as $file){
			copy('assets'.DIRECTORY_SEPARATOR.$file, $get_target_path(['assets',$file]));
		}
	}
})->options(['copy-assets']);

if(isset($_SERVER['REQUEST_URI']) && preg_match('/\.(?:css|js|png|jpg|gif)$/', $_SERVER["REQUEST_URI"])){
	return false;
}else{ 
	$app->run();
}
