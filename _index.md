<style>
.navbar, body > .homepage-hero, body > .hero-buttons, body > .homepage-footer { display:none; }
.github-ribbon { top:0; }
.homepage-hero { position:relative; background-color:#e74430; background-image: linear-gradient(-10deg, #e74430, red); color:white; opacity: 0.8; padding:128px 0 64px 0 !important;}
.homepage-hero h2 { font-size:20px; font-weight:normal; }
.homepage-hero h1 { font-size:48px; font-weight:bold; margin-bottom: 32px;}
body > .homepage-content, body > .homepage-content.container-fluid, body > .homepage-content > .container, body > .homepage-content > .container > .row, body > .homepage-content > .container > .row > .col-sm-10 { padding:0; margin:0; width:100%; }
.homepage-footer.container-fluid { background-color:#e8e8e8; font-weight:normal !important;}
code { color:#333; }
.btn-link {font-size: 16px !important;}
</style>

<div class="homepage-hero container-fluid">
<div class="container">
<div class="row">
<div class="text-center col-sm-12">
<h1>Laravel Datamapper</h1>
<h2>An easy to use data mapper ORM for Laravel 5 that fits perfectly to the approach of Domain Driven Design (DDD).</h2>
</div>
</div>
<div class="row">
<div class="col-sm-8 col-sm-offset-2">
</div>
</div>
</div>
</div>

<div class="hero-buttons container-fluid">
<div class="container text-center">
<a href="https://github.com/ProAI/laravel-datamapper" class="btn btn-secondary btn-hero">View On GitHub</a><a href="Getting_Started.html" class="btn btn-primary btn-hero">View Documentation</a>        <div class="clearfix"></div>
</div>
</div>

<a href="https://github.com/ProAI/laravel-datamapper" target="_blank" id="github-ribbon" class="github-ribbon hidden-print"><img src="https://s3.amazonaws.com/github/ribbons/forkme_right_darkblue_121621.png" alt="Fork me on GitHub"></a>

<div class="homepage-content container-fluid">
<div class="container">
<div class="row">
<div class="col-sm-8 col-sm-offset-2">
<div class="doc_content">

<p style="text-align:center; margin-bottom: 32px; margin-top: -16px"><a href="https://github.com/ProAI/laravel-datamapper/zipball/master" class="btn btn-link">Download .zip</a>
<a href="https://github.com/ProAI/laravel-datamapper/tarball/master" class="btn btn-link">Download .tar.gz</a></p>

<p style="text-align:center">An easy to use data mapper ORM for Laravel 5 that fits perfectly to the approach of Domain Driven Design (DDD). In general the Laravel Data Mapper is an extension to the Laravel Query Builder. You can build queries by using all of the query builder methods and in addition you can pass Plain Old PHP Objects (POPO's) to the builder and also return POPO's from the builder.</p>

<p style="text-align:center"><code>$user = $em->entity('Acme\Models\User')->find($id);</code></p>

<p style="text-align:center"><code>$users = $em->entity('Acme\Models\User')->all();</code></p>

<p style="text-align:center"><code>$em->insert($user);</code></p>

<p style="text-align:center"><code>$em->update($user);</code></p>

<p style="text-align:center"><code>$em->delete($user);</code></p>

<p style="text-align:center"><code>$users = $em->class('Acme\Models\User')->with('comments')->get();</code></p>

<p style="text-align:center; font-size:32px; margin-top:48px;"><a href="Getting_Started.html">Getting started!</a></p>

</div>
</div>
</div>
</div>
</div>



<div class="homepage-footer container-fluid">
<div class="container" style="padding:20px; text-align:center">
<small>
<a href="https://github.com/ProAI/laravel-datamapper" target="_blank">GitHub Repo</a> &middot;
<a href="https://github.com/ProAI/laravel-datamapper/issues" target="_blank">Help/Support/Bugs</a>
</small>
</div>
</div>