<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title></title>
	<link rel="stylesheet" href="/assets/style.css" />
	<link rel="stylesheet" href="/assets/github.css" />
</head>
<body>

	<div class="container">
		<div class="navigation-menu">

			<ul class="menu-root">
			<? foreach($docs as $folder=>$articles): ?>
			<? if(is_int($folder)): ?>
				<li> <a href="/<?= $this->as_path($articles)?>"><?= $this->as_title($articles)?></a> </li>
			<? else: ?>
				<? $folder = $this->as_path($folder) ?>
				<ul>
					<a href="/<?= $folder ?>"><?= $this->as_title($folder) ?></a>
				<? foreach($articles as $article): ?>
					<li <?= $this->is_current($folder) ? '' : 'style="display:none"' ?> > <a href="/<?= $folder?>/<?=$this->as_path($article)?>"><?= $this->as_title($article) ?></a> </li>
				<? endforeach ?>
				</ul>
			<? endif ?>
			<? endforeach ?>
			</ul>

		</div>
		<div class="main-content"> <?= $content ?> </div>
	</div>

	<script type="text/javascript" src="/assets/highlight.js"></script>
	<script type="text/javascript">hljs.initHighlightingOnLoad()</script>
</body>
</html>
