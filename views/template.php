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
				<li class="navigation-menu-folder"> <a href="/<?= $this->as_path($articles)?>"><?= $this->as_title($articles)?></a> </li>
			<? else: ?>
				<li class="navigation-menu-folder">
					<ul>
						<? $folder_path = $this->as_path($folder) ?>
						<a href="/<?= $folder_path ?>"><?= $this->as_title($folder) ?></a>
					<? foreach($articles as $article): ?>
						<li class="navigation-menu-article" <?= $this->is_current_folder($folder_path) ? '' : 'style="display:none"' ?> >
							<a class="<?= $this->is_current_article($folder_path, $this->as_path($article))?'current':''?>"
								href="/<?= $folder_path?>/<?=$this->as_path($article)?>"><?= $this->as_title($article) ?></a>
						</li>
					<? endforeach ?>
					</ul>
				</li>
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
