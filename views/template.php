<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title></title>
	<link rel="stylesheet" href="<?= $root?>assets/style.css" />
	<link rel="stylesheet" href="<?= $root?>assets/github.css" />
</head>
<body>

	<div class="container">
		<div class="navigation-menu">

			<ul class="menu-root">
			<? foreach($docs as $folder=>$articles): ?>
			<? if(is_string($articles)): ?>
				<li class="navigation-menu-folder">
					<a class="<?= $articles == $current_article ?'current':''?>"
						href="<?= $root?><?= $this->as_path($articles)?>.html"><?= $this->as_title($articles)?></a></li>
			<? else: ?>
				<li class="navigation-menu-folder">
					<ul>
						<? $folder_path = $this->as_path($folder) ?>
						<a href="<?= $root?><?= $folder_path ?>/<?= $this->as_path(current($articles))?>.html"><?= $this->as_title($folder) ?></a>
					<? foreach($articles as $article): ?>
						<li class="navigation-menu-article" <?= $folder == $current_folder ? '' : 'style="display:none"' ?> >
							<a class="<?= $article == $current_article ?'current':''?>"
								href="<?= $root?><?= $folder_path?>/<?=$this->as_path($article)?>.html"><?= $this->as_title($article) ?></a>
						</li>
					<? endforeach ?>
					</ul>
				</li>
			<? endif ?>
			<? endforeach ?>
			</ul>

			<ul class="menu-links">
				<? foreach($this->links as $text=>$href):?>
				<li>
					<a href="<?=$href?>"><?=$text?></a>
				</li>
				<? endforeach ?>
			</ul>

		</div>
		<div class="main-content">
			<? if(!empty($current_article)): ?>
			<h1><?= empty($current_folder) ? '' : $this->as_title($current_folder).':'?> <?= $this->as_title($current_article)?></h1>
			<? endif ?>
			<?= $content ?>
		</div>
	</div>

	<script type="text/javascript" src="<?= $root?>assets/highlight.js"></script>
	<script type="text/javascript">hljs.initHighlightingOnLoad()</script>
</body>
</html>
