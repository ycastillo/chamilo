function yox_youtube(){var i=jQuery,m={singleVideo:/^http:\/\/(?:www\.)?youtube.com\/watch\?v=([^\&]+)(.*)?/,playlist:/^http:\/\/(?:www\.)?youtube.com\/(?:view_play_list|my_playlists)\?p=([^\&]+)(.*)?/,user:/^http:\/\/(?:www\.)?youtube.com\/user\/([^\?]+)(?:\?(.*))?/,search:/^http:\/\/(?:www\.)?youtube.com\/results\?(.*)/};this.getImagesData=function(e,n){function o(c){var f=[];jQuery.each(c,function(j,b){if(k==="playlist")b=b.video;var g=b.title;g={thumbnailSrc:b.thumbnail[a.hqThumbnails?"hqDefault":
"sqDefault"],link:b.player["default"],media:{element:i("<div>",{className:"yoxview_element",html:"<object width='100%' height='100%'><param name='movie' value='"+(b.content["5"]+"&fs=1&hd=1")+"'</param><param name='allowFullScreen' value='true'></param><param name='wmode' value='transparent'></param><param name='allowScriptAccess' value='always'></param><embed src='"+(b.content["5"]+"&fs=1&hd=1")+"' type='application/x-shockwave-flash' allowfullscreen='true' allowscriptaccess='always' wmode='transparent' width='100%' height='100%'></embed></object>"}),
title:g,contentType:"flash",elementId:b.id,description:b.description}};i.extend(g.media,p(!!b.aspectRatio&&b.aspectRatio==="widescreen"));f.push(g)});return f}var h=false,a=jQuery.extend({},{url:"http://gdata.youtube.com/feeds/api/videos",setThumbnails:true,setSingleAlbumThumbnails:true,alt:"jsonc",thumbsize:64,v:2,format:5,hqThumbnails:false,aspectRatio:"auto"},e.dataSourceOptions),k;if(e.dataUrl){var d;for(regexType in m)if(d=e.dataUrl.match(m[regexType])){k=regexType;break}if(d){switch(k){case "singleVideo":h=
true;a.url+="/"+d[1];break;case "playlist":a.url="http://gdata.youtube.com/feeds/api/playlists/"+d[1];break;case "user":a.url="http://gdata.youtube.com/feeds/api/users/"+d[1]+"/uploads"}if(d=Yox.queryToJson(d.length==2?d[1]:d[2])){if(d.search_query){d.q=d.search_query;delete d.search_query}i.extend(a,d)}}}var p=function(){var c,f,j=16/9,b=false;if(!a.width&&!a.height)a.width=720;if(a.height&&!a.width||a.width&&!a.height){if(typeof a.aspectRatio==="string")if(a.aspectRatio==="auto")a.aspectRatio=4/
3;else{b=a.aspectRatio.split(":");a.aspectRatio=parseInt(b[0],10)/parseInt(b[1],10)}b=a.aspectRatio===16/9;if(a.height){c={height:a.height,width:a.height*j};b||(f={height:a.height,width:a.height*a.aspectRatio})}else{c={width:a.width,height:a.width/j};b||(f={width:a.width,height:a.width/a.aspectRatio})}}return function(g){return g?c:f}}(),l={};e.onLoadBegin&&e.onLoadBegin();i.jsonp({url:a.url,data:a,async:false,callbackParameter:"callback",success:function(c){if(h&&!c.data||!h&&(!c.data.items||c.data.items.length===
0))e.onNoData&&e.onNoData();else{l.images=o(h?[c.data]:c.data.items);if(!h)if(c=c.data.title)l.title=c;n&&n(l);e.onLoadComplete&&e.onLoadComplete()}},error:function(){e.onLoadError&&e.onLoadError("YouTube plugin encountered an error while retrieving data")}})}};
