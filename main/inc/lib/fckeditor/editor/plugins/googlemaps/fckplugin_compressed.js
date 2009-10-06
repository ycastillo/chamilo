﻿if (typeof(FCKConfig.GoogleMaps_Key)!='string'){alert('Error.\r\nThe configuration doesn\'t contain the Google Maps key.\r\nPlease read the Configuration section.');window.open(FCKPlugins.Items['googlemaps'].Path+'docs/'+FCKLang.GMapsHelpFile+'#configure');};if (!FCKConfig.GoogleMaps_Key||FCKConfig.GoogleMaps_Key.length===0){for(var name in FCKConfig.ToolbarSets) RemoveButtonFromToolbarSet(FCKConfig.ToolbarSets[name],'googlemaps');};function RemoveButtonFromToolbarSet(A,B){if (!A) return;for (var x=0;x<A.length;x++){var C=A[x];if (!C) continue;if (typeof(C)=='object'){for (var j=0;j<C.length;j++){if (C[j]==B){C.splice(j,1);A[x]=C;return;}}}}};FCKCommands.RegisterCommand('googlemaps',new FCKDialogCommand('googlemaps',FCKLang.DlgGMapsTitle,FCKPlugins.Items['googlemaps'].Path+'dialog/googleMaps.html',450,428));var oGoogleMapsButton=new FCKToolbarButton('googlemaps',FCKLang.GMapsBtn,FCKLang.GMapsBtnTooltip);oGoogleMapsButton.IconPath=FCKPlugins.Items['googlemaps'].Path+'images/mapIcon.gif';FCKToolbarItems.RegisterItem('googlemaps',oGoogleMapsButton);if (typeof FCKCommentsProcessor==='undefined'){var FCKCommentsProcessor=FCKDocumentProcessor.AppendNew();FCKCommentsProcessor.ProcessDocument=function(A){if (FCK.EditMode!=FCK_EDITMODE_WYSIWYG) return;if (!A) return;if (A.evaluate) this.findCommentsXPath(A);else{if (A.all) this.findCommentsIE(A.body);else this.findComments(A.body);}};FCKCommentsProcessor.findCommentsXPath=function(A) {var B=A.evaluate('//body//comment()',A.body,null,XPathResult.UNORDERED_NODE_SNAPSHOT_TYPE,null);for (var i=0;i<B.snapshotLength;i++){this.parseComment(B.snapshotItem(i));}};FCKCommentsProcessor.findCommentsIE=function(A) {var B=A.getElementsByTagName('!');for(var i=B.length-1;i>=0;i--){var C=B[i];if (C.nodeType==8) this.parseComment(C);}};FCKCommentsProcessor.findComments=function(A){if (A.nodeType==8){this.parseComment(A);}else{if (A.hasChildNodes()){var B=A.childNodes;for (var i=B.length-1;i>=0;i--) this.findComments(B[i]);}}};FCKCommentsProcessor.parseComment=function(A){var B=A.nodeValue;var C=(FCKConfig.ProtectedSource._CodeTag||'PS\\.\\.');var D=new RegExp("\\{"+C+"(\\d+)\\}","g");if (D.test(B)){var E=RegExp.$1;var F=FCKTempBin.Elements[E];var G=this.ParserHandlers;if (G){for (var i=0;i<G.length;i++) G[i](A,F,E);}}};FCKCommentsProcessor.AddParser=function(A){if (!this.ParserHandlers) this.ParserHandlers=[A];else{if (this.ParserHandlers.IndexOf(A)==-1) this.ParserHandlers.push(A);}}};var GoogleMaps_CommentsProcessorParser=function(A,B,C){if (FCK.GoogleMapsHandler.detectMapScript(B)){var D=FCK.GoogleMapsHandler.createNew();D.parse(B);D.createHtmlElement(A,C);}else{if (FCK.GoogleMapsHandler.detectGoogleScript(B)) A.parentNode.removeChild(A);}};FCKCommentsProcessor.AddParser(GoogleMaps_CommentsProcessorParser);FCK.ContextMenu.RegisterListener({AddItems:function(A,B,C){if (C=='IMG'&&B.getAttribute('MapNumber')){A.RemoveAllItems();A.AddItem('googlemaps',FCKLang.DlgGMapsTitle,oGoogleMapsButton.IconPath);}}});FCK.RegisterDoubleClickHandler(editMap,'IMG');function editMap(A){if (!A.getAttribute('MapNumber')) return;FCK.Commands.GetCommand('googlemaps').Execute();};FCK.GoogleMapsHandler={maps:{},getMap:function(A){return this.maps[A];},detectMapScript:function(A){if (!(/FCK googlemaps v1\.(\d+)/.test(A))) return false;return true},publicKey:function() {if (FCKConfig.GoogleMaps_PublicKey) return FCKConfig.GoogleMaps_PublicKey;return FCKConfig.GoogleMaps_Key;}(),detectGoogleScript:function(A){if (/FCK googlemapsEnd v1\./.test(A)) return true;if (!/^<script src="http:\/\/maps\.google\.com\/.*key=(.*)("|&)/.test(A)) return false;this.publicKey=RegExp.$1;return (true);},GenerateGoogleScript:function(){return '\r\n<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key='+FCKConfig.GoogleMaps_Key+'" type="text/javascript" charset="utf-8"></script>';},createNew:function(){var A=new FCKGoogleMap();this.maps[A.number]=A;return A;},BuildEndingScript:function(){var A='// FCK googlemapsEnd v1.97';var B=[];B.push('\r\n<script type="text/javascript">');B.push(A);B.push('function AddMarkers( map, aPoints )');B.push('{');B.push('	for (var i=0; i<aPoints.length ; i++)');B.push('	{');B.push('		var point = aPoints[i] ;');B.push('		map.addOverlay( createMarker(new GLatLng(point.lat, point.lon), point.text) );');B.push('	}');B.push('}');B.push('function createMarker( point, html )');B.push('{');B.push('	var marker = new GMarker(point);');B.push('	GEvent.addListener(marker, "click", function() {');B.push('		marker.openInfoWindowHtml(html, {maxWidth:200});');B.push('	});');B.push('	return marker;');B.push('}');var C=this.CreatedMapsNames;for (var i=0;i<C.length;i++){B.push('if (window.addEventListener) {');B.push('    window.addEventListener("load", CreateGMap'+C[i]+', false);');B.push('} else {');B.push('    window.attachEvent("onload", CreateGMap'+C[i]+');');B.push('}');};B.push('onunload = GUnload ;');B.push('</script>');return B.join('\r\n');},CreatedMapsNames:[],GetXHTMLAfter:function(A,B,C,D){if (FCK.GoogleMapsHandler.CreatedMapsNames.length>0){D+=FCK.GoogleMapsHandler.BuildEndingScript();};FCK.GoogleMapsHandler.CreatedMapsNames=[];return D;},previousProcessor:FCKXHtml.TagProcessors['img']};var FCKGoogleMap=function(){var A=new Date();this.number=''+A.getFullYear()+A.getMonth()+A.getDate()+A.getHours()+A.getMinutes()+A.getSeconds();this.width=FCKConfig.GoogleMaps_Width||400;this.height=FCKConfig.GoogleMaps_Height||240;this.centerLat=FCKConfig.GoogleMaps_CenterLat||37.4419;this.centerLon=FCKConfig.GoogleMaps_CenterLon||-122.1419;this.zoom=FCKConfig.GoogleMaps_Zoom||11;this.markerPoints=[];this.LinePoints='';this.LineLevels='';this.mapType=0;this.WrapperClass=FCKConfig.GoogleMaps_WrapperClass||'';};FCKGoogleMap.prototype.createHtmlElement=function(A,B){var C=FCK.EditorDocument.createElement('IMG');if (!A){B=FCKTempBin.AddElement(this.BuildScript());var D=(FCKConfig.ProtectedSource._CodeTag||'PS..');A=FCK.EditorDocument.createComment('{'+D+B+'}');FCK.InsertElement(A);};C.contentEditable=false;C.setAttribute('_fckrealelement',FCKTempBin.AddElement(A),0);C.setAttribute('_fckBinNode',B,0);C.src=FCKConfig.FullBasePath+'images/spacer.gif';C.style.display='block';C.style.border='1px solid black';C.style.background='white center center url("'+FCKPlugins.Items['googlemaps'].Path+'images/maps_res_logo.png") no-repeat';C.setAttribute("MapNumber",this.number,0);A.parentNode.insertBefore(C,A);A.parentNode.removeChild(A);this.updateHTMLElement(C);return C;};FCKGoogleMap.prototype.updateScript=function(A){this.updateDimensions(A);var B=A.getAttribute('_fckBinNode');FCKTempBin.Elements[B]=this.BuildScript();};FCKGoogleMap.prototype.updateHTMLElement=function(A){A.width=this.width;A.height=this.height;A.src=this.generateStaticMap();A.style.border=0;if (this.WrapperClass!=='') A.className=this.WrapperClass;};FCKGoogleMap.prototype.generateStaticMap=function(){var w=Math.min(this.width,640);var h=Math.min(this.height,640);var A=['roadmap','satellite','hybrid','terrain'];return 'http://maps.google.com/staticmap?center='+this.centerLat+','+this.centerLon+'&zoom='+this.zoom+'&size='+w+'x'+h+'&maptype='+A[this.mapType]+this.generateStaticMarkers()+'&key='+FCKConfig.GoogleMaps_Key};FCKGoogleMap.prototype.generateStaticMarkers=function(){if (this.markerPoints.length==0) return '';var A=[];for (var i=0;i<this.markerPoints.length;i++){var B=this.markerPoints[i];A.push(B.lat+','+B.lon);};return ('&markers='+A.join('|'));};FCKGoogleMap.prototype.updateDimensions=function(A){var B,C;var D=/^\s*(\d+)px\s*$/i;if (A.style.width){var E=A.style.width.match(D);if (E){B=E[1];A.style.width='';A.width=B;}};if (A.style.height){var F=A.style.height.match(D);if (F){C=F[1];A.style.height='';A.height=C;}};this.width=B?B:A.width;this.height=C?C:A.height;};FCKGoogleMap.prototype.decodeText=function(A){return A.replace(/<\\\//g,"</").replace(/\\n/g,"\n").replace(/\\'/g,"'").replace(/\\\\/g,"\\");};FCKGoogleMap.prototype.encodeText=function(A){return A.replace(/\\/g, "\\\\").replace(/'/g,"\\'").replace(/\n/g,"\\n").replace(/<\//g,"<\\/");};FCKGoogleMap.prototype.parse=function(A){if (!(/FCK googlemaps v1\.(\d+)/.test(A))) return false;var B=parseInt(RegExp.$1,10);var C=/<div id="gmap(\d+)" style="width\:\s*(\d+)px; height\:\s*(\d+)px;">/;if (C.test(A)){delete FCK.GoogleMapsHandler.maps[this.number];this.number=RegExp.$1;FCK.GoogleMapsHandler.maps[this.number]=this;this.width=RegExp.$2;this.height=RegExp.$3;};var D=/map\.setCenter\(new GLatLng\((-?\d{1,3}\.\d{1,6}),(-?\d{1,3}\.\d{1,6})\), (\d{1,2})\);/;if (D.test(A)){this.centerLat=RegExp.$1;this.centerLon=RegExp.$2;this.zoom=RegExp.$3;};if (B<=5){var E,F=0,G=0;var H=/var text\s*=\s*("|')(.*)\1;\s*\n/;if (H.test(A)){E=RegExp.$2;};var I=/var point\s*=\s*new GLatLng\((-?\d{1,3}\.\d{1,6}),(-?\d{1,3}\.\d{1,6})\)/;if (I.test(A)){F=RegExp.$1;G=RegExp.$2;};if (F!=0&&G!=0) this.markerPoints.push({lat:F,lon:G,text:this.decodeText(E)});}else{var J=/\{lat\:(-?\d{1,3}\.\d{1,6}),\s*lon\:(-?\d{1,3}\.\d{1,6}),\s*text\:("|')(.*)\3}(?:,|])/;var K;var L=A;var M=0;var N=L.length;var O,P;while (M!=N) {O=J.exec(L);if (O&&O.length>0) {P=L.indexOf(O[0]);M+=P;this.markerPoints.push({lat:O[1],lon:O[2],text:this.decodeText(O[4])});L=L.substr(P+O[0].length);M+=O[0].length;} else {break;}}};var Q=/var encodedPoints\s*=\s*("|')(.*)\1;\s*\n/;if (Q.test(A)){this.LinePoints=RegExp.$2;};var R=/var encodedLevels\s*=\s*("|')(.*)\1;\s*\n/;if (R.test(A)){this.LineLevels=RegExp.$2;};var S=/setMapType\([^\[]*\[\s*(\d+)\s*\]\s*\)/;if (S.test(A)){this.mapType=RegExp.$1;};if (B>=9){var T=/<div class=("|')(.*)\1.*\/\/wrapper/;if (T.test(A)) this.WrapperClass=RegExp.$2;else this.WrapperClass='';};return true;};FCKGoogleMap.prototype.BuildScript=function(){var A='// FCK googlemaps v1.97';var B=[];B.push('\r\n<script type="text/javascript">');B.push(A);if (this.WrapperClass!=='') B.push('document.write(\'<div class="'+this.WrapperClass+'">\'); //wrapper');B.push('document.write(\'<div id="gmap'+this.number+'" style="width:'+this.width+'px; height:'+this.height+'px;">.<\\\/div>\');');if (this.WrapperClass!=='') B.push('document.write(\'<\\\/div>\'); ');B.push('function CreateGMap'+this.number+'() {');B.push('	if(!GBrowserIsCompatible()) return;');B.push('	var allMapTypes = [G_NORMAL_MAP, G_SATELLITE_MAP, G_HYBRID_MAP, G_PHYSICAL_MAP] ;');B.push('	var map = new GMap2(document.getElementById("gmap'+this.number+'"), {mapTypes:allMapTypes});');B.push('	map.setCenter(new GLatLng('+this.centerLat+','+this.centerLon+'), '+this.zoom+');');B.push('	map.setMapType( allMapTypes[ '+this.mapType+' ] );');B.push('	map.addControl(new GSmallMapControl());');B.push('	map.addControl(new GMapTypeControl());');var C=[];for (var i=0;i<this.markerPoints.length;i++){var D=this.markerPoints[i];C.push('{lat:'+D.lat+', lon:'+D.lon+', text:\''+this.encodeText(D.text)+'\'}');};B.push('	AddMarkers( map, ['+C.join(',\r\n')+'] ) ;');if ((this.LinePoints!=='')&&(this.LineLevels!=='')){B.push('var encodedPoints = "'+this.LinePoints+'";');B.push('var encodedLevels = "'+this.LineLevels+'";');B.push('');B.push('var encodedPolyline = new GPolyline.fromEncoded({');B.push('	color: "#3333cc",');B.push('	weight: 5,');B.push('	points: encodedPoints,');B.push('	levels: encodedLevels,');B.push('	zoomFactor: 32,');B.push('	numLevels: 4');B.push('	});');B.push('map.addOverlay(encodedPolyline);');};B.push('}');B.push('</script>');return B.join('\r\n');};FCKXHtml.GetXHTML=Inject(FCKXHtml.GetXHTML,null,FCK.GoogleMapsHandler.GetXHTMLAfter);FCKXHtml.TagProcessors.img=function(A,B,C){if (B.getAttribute('MapNumber')){var D=FCK.GoogleMapsHandler.getMap(B.getAttribute('MapNumber'));FCK.GoogleMapsHandler.CreatedMapsNames.push(D.number);D.updateScript(B);A=FCK.GetRealElement(B);if (FCK.GoogleMapsHandler.CreatedMapsNames.length==1){var E=FCKTempBin.AddElement(FCK.GoogleMapsHandler.GenerateGoogleScript());var F=(FCKConfig.ProtectedSource._CodeTag||'PS..');oScriptCommentNode=C.ownerDocument.createComment('{'+F+E+'}');C.appendChild(oScriptCommentNode);};return C.ownerDocument.createComment(A.nodeValue);};if (typeof FCK.GoogleMapsHandler.previousProcessor=='function') A=FCK.GoogleMapsHandler.previousProcessor(A,B,C);else A=FCKXHtml._AppendChildNodes(A,B,false);return A;};function Inject(A,B,C) {return function() {if (typeof(B)=='function') arguments=B.apply(this,arguments)||arguments;var D,E=[].slice.call(arguments);E.push(A.apply(this,E));if (typeof(C)=='function') D=C.apply(this,E);return (typeof(D)!='undefined')?D:E.pop();};};
