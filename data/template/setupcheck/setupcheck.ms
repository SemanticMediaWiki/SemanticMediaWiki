<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
	<meta http-equiv="refresh" content="{{refresh}}" charset="UTF-8" />
	<title>{{title}}</title>
	<style media='screen'>
		body {
			color: #000;
			background-color: #fff;
			font-family: sans-serif;
			padding: 0em;
		}
		img, h1, h2, ul  {
			text-align: left;
			margin: 0.1em 0 0.3em;
			margin-left:10px;
		}
		p, h2, h4 {
			text-align: left;
			margin: 0.5em 0 1em;
			margin-left:10px;
			margin-right: 10px;
		}
		.title {
		}
		.nav {
			height: 60px;
			background-color:#f8f9fa;
			padding-bottom:2px;
			margin-top: -8px;
			margin-left: -8px;
			margin-right: -8px;
			box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
			border-bottom: 1px solid #ddd!important;
		}
		.nav-info {
			display: inline-block;
			font-weight: 400;
			color: #fff;
			text-align: center;
			vertical-align: middle;
			font-size: 1rem;
			line-height: 1.5;
			border-radius: .15rem;
			float: right;
			padding: 5px 30px;
			margin-top: 17px;
			margin-right: 15px;
		}
		.sticky {
			position: fixed;
			top: 0;
			width: 100%;
		}
		.content {
			margin-top: 80px;
			line-height:1.4em;
			font-family: "Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji";
		}
		h1 {
			font-size: 140%;
		}
		h2 {
			font-size: 110%;
		}
		h3, h4 {
			font-size: 100%;
			margin-left:10px;
		}
		code {
			color: black;
			background-color: #f9f9f9;
			border: 1px solid #ddd;
			border-radius: 2px;
			padding: 1px 4px;
		}
		p + h4 {
			margin-top: 20px;
		}
		.errorbox {
			color: #d33;
			border-color: #fac5c5;
			background-color: #fae3e3;
			border: 0px solid;
			word-break: normal;
			padding: 0.5em 0.5em;
			display: inline-block;
			zoom: 1;
			margin-left:10px;
			margin-right:10px;
		}
		.errorbox + .errorbox {
			margin-top: 20px;
		}
		pre {
			margin: 0px;
			white-space: pre-wrap;       /* css-3 */
			white-space: -moz-pre-wrap;  /* Mozilla, since 1999 */
			white-space: -pre-wrap;      /* Opera 4-6 */
			white-space: -o-pre-wrap;    /* Opera 7 */
			word-wrap: break-word;       /* Internet Explorer 5.5+ */
		}
		.progress-bar-animated {
			animation: progress-bar-stripes 2s linear infinite;
		}
		.progress-bar-striped {
			background-image: linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent);background-size: 1rem 1rem;
		}
		.progress-bar-section {
			margin-right: 20px;
			margin-bottom:10px;
			margin-top:10px;
			white-space: nowrap;
			padding: 0 0 8px 0;
		}
		.progress-bar {
			background-color: #eee;
			transition: width .6s ease;
			justify-content: center;
			display: flex;
			white-space: nowrap;
		}
		.section {
			margin-right: 8px;
			border-bottom: 1px solid #dee2e6!important;
			padding-bottom: 10px;
		}
		.section:first-of-type {
			margin-top: 30px;
		}
		@keyframes progress-bar-stripes {
			from { background-position: 28px 0; } to { background-position: 0 0; }
		}
	</style>
</head>
<body>
<div class="nav sticky" style=" border-bottom: 4px solid {{borderColor}};">
<span class="nav-info" style="background-color:{{borderColor}}">{{indicator}}</span>
<h1 style="color:#222;margin-left:18px;padding-top:20px;"><img style='margin-top: 2px;margin-right:10px;margin-left:2px;float:left;' src='{{logo}}' height='25' width='30'>{{title}}</h1></div>
<div class="content">{{content}}</div>
</body>
</html>