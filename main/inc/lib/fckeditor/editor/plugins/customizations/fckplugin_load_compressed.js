﻿FCKPlugin.prototype.Load=function(){switch (this.Name){case 'dragresizetable':case 'tablecommands':case 'ImageManager':case 'prompt':this.AvailableLangs=null;this.AvailableLangs=[];break;case 'asciimath':case 'asciisvg':case 'customizations':case 'audio':case 'fckEmbedMovies':case 'flvPlayer':case 'youtube':case 'googlemaps':case 'mimetex':case 'wikilink':case 'imgmap':break;default:LoadScript(this.Path+'lang/en.js');};if (this.AvailableLangs.length>0){var A;if (this.AvailableLangs.IndexOf(FCKLanguageManager.ActiveLanguage.Code)>=0) A=FCKLanguageManager.ActiveLanguage.Code;else A=this.AvailableLangs[0];LoadScript(this.Path+'lang/'+A+'.js');};var B;switch (this.Name){case 'asciimath':case 'asciisvg':case 'audio':case 'autogrow':case 'customizations':case 'dragresizetable':case 'fckEmbedMovies':case 'flvPlayer':case 'googlemaps':case 'ImageManager':case 'imgmap':case 'mimetex':case 'prompt':case 'tablecommands':case 'wikilink':case 'youtube':B=(window.document.location.toString().indexOf('fckeditor.original.html')!=-1)?'fckplugin.js':'fckplugin_compressed.js';break;default:B='fckplugin.js';};LoadScript(this.Path+B);};
