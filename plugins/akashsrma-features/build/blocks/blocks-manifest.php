<?php
// This file is generated. Do not modify it manually.
return array(
	'project-links' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'akashsrma/project-links',
		'title' => 'Code & live links',
		'category' => 'text',
		'icon' => 'admin-links',
		'description' => 'Code → · Live → links for a project or learning post, or a note when the work is private.',
		'textdomain' => 'akashsrma-features',
		'usesContext' => array(
			'postId',
			'postType'
		),
		'supports' => array(
			'html' => false,
			'multiple' => false,
			'spacing' => array(
				'margin' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	)
);
