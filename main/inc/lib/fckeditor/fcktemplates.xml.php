<?php header('Content-Type: text/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="utf-8" ?>';
require_once('../../conf/configuration.php');

$IMConfig['base_url'] = $rootWeb.'main/img/gallery/';
?>
<Templates imagesBasePath="fck_template/images/">
	<Template title="Content" image="2.png">
		<Description>One main content</Description>
		<Html>
			<![CDATA[
				    <div id="content" class="course">
					    <h3>H3 Title</h3>
					    <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit praesent luptatum zzril delenit augue duis dolore te feugait nulla facilisi.</p>
					    
					    <ul>
						    <li>List Item</li>
						    <li>List Item</li>
						    <li>List Item</li>
						    <li>List Item</li>
						    <li>List Item</li>
					    </ul>
				    </div>
			]]>
		</Html>
	</Template>
	<Template title="content with array" image="8.png">
		<Description>A template with array.</Description>
		<Html>
			<![CDATA[
				    <div id="content" class="course">
					    <h3>H3 Title</h3>
					    
					    <table class="data">
						    <tr>
							    <td class="item">Item #1</td>
							    <td>data</td>
							    <td>data</td>
							    <td>data</td>
							    <td>data</td>
						    </tr>
						    <tr>
							    <td class="item">Item #2</td>
							    <td>data</td>
							    <td>data</td>
							    <td>data</td>
							    <td>data</td>
						    </tr>
						    <tr>
							    <td class="item">Item #3</td>
							    <td>data</td>
							    <td>data</td>
							    <td>data</td>
							    <td>data</td>
						    </tr>
					    </table>
					    
				    </div>
			]]>
		</Html>
	</Template>
	<Template title="Three areas" image="17.png">
		<Description>Three areas for content.</Description>
		<Html>
			<![CDATA[
				    <div class="course">
				    <div id="right">
 					    <p><img class="image" src="<?php echo $rootWeb; ?>main/img/gallery/pointer-right.png" /></p>
					    <p>Some text</p>
				    </div>
				    
				    <div id="left">
					    <h3>H3 Title</h3>
					    
					    <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.</p>
				    </div>
				    
				    
				    
				    <div id="left">
					    <h3>H3 Title</h3>
					    <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.</p>
				    </div>
				    </div>
				    ]]>
			    </Html>
		    </Template>
		    <Template title="Three areas" image="18.png">
			    <Description>Three areas for content.</Description>
			    <Html>
				    <![CDATA[
				    <div class="course">
						   <div id="left">
							   <h3>H3 Title</h3>
							   <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit praesent luptatum zzril delenit augue duis dolore te feugait nulla facilisi.</p>
						   </div>
						   
						   <div id="right">
							   <p><img class="image" src="<?php echo $rootWeb; ?>main/img/gallery/pointer-right.png" /></p>
						   </div>
						   
						   <div id="content">
							   <h3>H3 Title</h3>
							   <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit praesent luptatum zzril delenit augue duis dolore te feugait nulla facilisi.</p>
							   
							   <ul>
								   <li>List Item</li>
								   <li>List Item</li>
								   <li>List Item</li>
								   <li>List Item</li>
								   <li>List Item</li>
							   </ul>
						   </div>
				    </div>
						   ]]>
					   </Html>
				   </Template>
				   <Template title="Four areas" image="20.png">
					   <Description>Four areas for content.</Description>
					   <Html>
						   <![CDATA[
				    <div class="course">
								  <div class="left">
									  <p><img class="image" src="<?php echo $rootWeb; ?>main/img/gallery/pointer-left.png" /></p>
								  </div>
								  
								  <div class="right">
									  <h3>H3 Title</h3>
									  <div class="image">Image placehoder</div>
									  <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit praesent luptatum zzril delenit augue duis dolore te feugait nulla facilisi.</p>
								  </div>
								  
								  <br style="clear: both;" />
								  
								  <div class="left">
									  <h3>H3 Title</h3>
									  <div class="image">Image placehoder</div>
									  <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit praesent luptatum zzril delenit augue duis dolore te feugait nulla facilisi.</p>
								  </div>
								  
								  <div class="right">
									  <h3>H3 Title</h3>
									  <div class="image">Image placehoder</div>
									  <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit praesent luptatum zzril delenit augue duis dolore te feugait nulla facilisi.</p>
								  </div>
								  </div>
								  ]]>
							  </Html>
						  </Template>
</Templates>
