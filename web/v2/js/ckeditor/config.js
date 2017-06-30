/**
 * @license Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */
CKEDITOR.addStylesSet( 'my_styles',[    
		/*
		// Block Styles   
		{ name : 'Blue Title', element : 'h2', styles : { 'color' : 'Blue' } },  
		{ name : 'Red Title' , element : 'h3', styles : { 'color' : 'Red' } },  
		// Inline Styles   
		 { name : 'CSS Style', element : 'span', attributes : { 'class' : 'my_style' } },    
		 { name : 'Marker: Yellow', element : 'span', styles : { 'background-color' : 'Yellow' } }
		 */
		{name:"margin0 p", element:'p' , styles:{'margin':0}},
		{name:"margin0 ul", element:'ul', styles:{'margin':0}},
		{name:"margin0 ol", element:'ol', styles:{'margin':0}}
	 ]);
CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';
	//("-") 为空间栏的水平分割，("/") 为换行。
	config.toolbar  = [ 
		//['Source','-','Save','NewPage','Preview','-','Templates'],
		//['Cut','Copy','Paste','PasteText','PasteFromWord','-','Print', 'SpellChecker', 'Scayt'],
		//['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
		//['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'],
		//'/',
		['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
		['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
		['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
		//['Link','Unlink','Anchor'],
		//['Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak'],
		'/',
		['Styles','Format','Font','FontSize'],
		['TextColor','BGColor'],
		//['Maximize', 'ShowBlocks','-','About']
		];
	//config.toolbar = 'Basic';
	config.uiColor = '#FAFAFA';
	config.language = 'zh-cn';

	//config.enterMode=1;   //回车的时候增加的是p
	config.enterMode=2;     //回车的时候增加的是<br>//需要的就是这种情况
	//config.enterMode=3;   //回车的时候增加的是div
	//config.stylesCombo_stylesSet = 'my_styles';
	//config.format_p = {element:'p',styles:{'margin':0}};
};

