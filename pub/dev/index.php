<?php
$dir = '/www/stockbd';// dirname(dirname(__FILE__)); // location of  root dir
include $dir."/config.php";

require SBD_ROOT.'/pub/dev/include/common.php';
define('INDEX_PAGE', 1); // this define helps add appropriate header
include SBD_ROOT."/pub/dev/header.php";
?>
       	<div id="main">
			<h1>Template Info</h1>
				
            <p><strong>Underground</strong> is a free, W3C-compliant, CSS-based website template
            by <a href="http://www.styleshout.com/">styleshout.com</a>. This work is
            distributed under the <a rel="license" href="http://creativecommons.org/licenses/by/2.5/">
            Creative Commons Attribution 2.5  License</a>, which means that you are free to
            use and modify it for any purpose. All I ask is that you give me credit by including a <strong>link back</strong> to
            <a href="http://www.styleshout.com/">my website</a>.
            </p>

            <p>
            You can find more of my free template designs at <a href="http://www.styleshout.com/">my website</a>.
            For premium commercial designs, you can check out
            <a href="http://www.dreamtemplate.com" title="Website Templates">DreamTemplate.com</a>.
            </p>
						
			<p class="comments clear">
				<a href="index.html">Read more</a> |
				<a href="index.html">comments(3)</a> |
				Oct 18 2006
			</p>
			
			<a name="SampleTags"></a>
			<h1>Sample Tags</h1>
				
			<h3>Code</h3>				
			<p>
			<code>
			code-sample { <br />
			font-weight: bold;<br />
			font-style: italic;<br />				
			}
			</code></p>	
				
			<h3>Example Lists</h3>
				
			<ol>
				<li>example of</li>
				<li>ordered list</li>								
			</ol>	
							
			<ul>
				<li>example of</li>
				<li>unordered list</li>								
			</ul>				
				
			<h3>Blockquote</h3>			
			<blockquote><p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy 
			nibh euismod tincidunt ut laoreet dolore magna aliquam erat....</p></blockquote>
				
			<h3>Image and text</h3>
			<p><a href="http://getfirefox.com/"><img src="images/firefox-gray.jpg" width="100" height="120" alt="firefox" class="float-left" /></a>
			Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec libero. Suspendisse bibendum. 
			Cras id urna. Morbi tincidunt, orci ac convallis aliquam, lectus turpis varius lorem, eu 
			posuere nunc justo tempus leo. Donec mattis, purus nec placerat bibendum, dui pede condimentum 
			odio, ac blandit ante orci ut diam. Cras fringilla magna. Phasellus suscipit, leo a pharetra 
			condimentum, lorem tellus eleifend magna, eget fringilla velit magna id neque. Curabitur vel urna. 
			In tristique orci porttitor ipsum. Aliquam ornare diam iaculis nibh. Proin luctus, velit pulvinar 
			ullamcorper nonummy, mauris enim eleifend urna, congue egestas elit lectus eu est. 				
			</p>
								
			<h3>Example Form</h3>
			
			<form action="#">		
				<p>
				<label>Name</label>
				<input name="dname" value="Your Name" type="text" size="30" />
				<label>Email</label>
				<input name="demail" value="Your Email" type="text" size="30" />
				<label>Your Comments</label>
				<textarea rows="5" cols="5"></textarea>
				<br />	
				<input class="button" type="submit" />		
				</p>		
			</form>				
			<br />					
											
	</div>

        <div id="sidebar" >
			<h1>User info</h1>
			<div class="left-box">
			<p>
			<?php 
			if($cur_user["userid"]=="guest")
			     echo 'You are not logged in. Please <a href="login.php?redirect_url=index.php">Log in</a> or <a href="register.php">register</a>.';
			else
			     echo 'Welcome '.$cur_user["userid"].' <a href=login.php?action=logout> Log out </a>';
			?>
			</p>
			</div>
			
			<h1>Menu</h1>
			<div class="left-box">
				<ul class="sidemenu">
					<li><a href="index.html">Home</a></li>
					<li><a href="#TemplateInfo">Template Info</a></li>
					<li><a href="#SampleTags">Sample Tags</a></li>
					<li><a href="http://www.styleshout.com/">More Free Templates</a></li>
					<li><a href="http://www.dreamtemplate.com" title="Web Templates">Web Templates</a></li>
				</ul>
			</div>

			<h1>Sponsors</h1>
			<div class="left-box">
                <ul class="sidemenu">
                    <li><a href="http://www.dreamtemplate.com" title="Website Templates">DreamTemplate</a></li>
                    <li><a href="http://www.themelayouts.com" title="WordPress Themes">ThemeLayouts</a></li>
                    <li><a href="http://www.imhosted.com" title="Website Hosting">ImHosted.com</a></li>
                    <li><a href="http://www.dreamstock.com" title="Stock Photos">DreamStock</a></li>
                    <li><a href="http://www.evrsoft.com" title="Website Builder">Evrsoft</a></li>
                    <li><a href="http://www.webhostingwp.com" title="Web Hosting">Web Hosting</a></li>
                </ul>
			</div>


			<h1>Wise Words</h1>
			<div class="left-box">
				<p>&quot;Big men and big personalities make mistakes and admit them.
				 It is the little man who is afraid to admit he has been wrong&quot;</p>

				<p class="align-right">- Dr. Maxwell Maltz</p>
			</div>

			<h1>Support Styleshout</h1>
			<div class="left-box">
				<p>If you are interested in supporting my work and would like to contribute, you are
				welcome to make a small donation through the
				<a href="http://www.styleshout.com/">donate link</a> on my website - it will
				be a great help and will surely be appreciated.</p>
			</div>

	</div>
		

<!-- footer starts here -->
<?php include SBD_ROOT.'/pub/dev/footer.php'; ?>	

<?php
// End the transaction
$db->end_transaction();

// Close the db connection (and free up any result data)
$db->close();

?>
