tinyMCEPopup.requireLangPack("GoogleMaps");

var GoogleMapsDialog = {
	init : function() {
		var ed = tinyMCEPopup.editor, map;
		map = ed.getSelectedPluginGoogleMaps();
		
		if(map){
			/* Let's check the size of the image on the selection, and update the map width and height on the form */
			var nodeElement = ed.selection.getNode(), s;
			if (nodeElement.nodeName == 'IMG') {
				if ( ed.dom.hasClass(nodeElement, 'GoogleMaps') ){
					map.width = (s=nodeElement.width)?s:map.width;
					map.height = (s=nodeElement.height)?s:map.height;
				}
			}
			
			var form = document.forms[0];
			if(form){
				var v;
				if(v = parseFloat(map.latitude)){
					form.latitude.value = v;
				}
				if(v = parseFloat(map.longitude)){
					form.longitude.value = v;
				}
				if(v = parseInt(map.width)){
					form.width.value = v;
				}
				if(v = parseInt(map.height)){
					form.height.value = v;
				}
				if(v = parseInt(map.zoom)){
					form.zoomlevel.value = v;
				}
				if(v = parseInt(map.hudzp)){
					form.hudzp.value = v;
				}
				if(v = parseInt(map.hudmt)){
					form.hudmt.value = v;
				}
				if(v = parseInt(map.huds)){
					form.huds.value = v;
				}
				if(v = parseInt(map.mapstyle)){
					form.mapstyle.value = v;
				}
				if(map.iconImage){
					form.icon_image.value = map.iconImage;
				}
				if(map.iconShadowImage){
					form.icon_image_shadow.value = map.iconShadowImage;
				}
				if(map.infoWindowContent){
					form.info_window_content.value = map.infoWindowContent;
				}
				if(map.iconTitle){
					form.icon_title.value = map.iconTitle;
				}
			}
		}
	},
	insert : function() {
		var ed = tinyMCEPopup.editor, args = {}, el;
		var f = document.forms['GoogleMaps'], args = {};
		var strHtml = '';
		var latitude = parseFloat(f.latitude.value);
		var longitude = parseFloat(f.longitude.value);
		var width = parseInt(f.width.value);
		var height = parseInt(f.height.value);
		var zoom = parseInt(f.zoomlevel.value);
		var hudzp = f.hudzp.value;
		var hudmt = f.hudmt.value;
		var huds = f.huds.value;
		var mapstyle = f.mapstyle.value;
		var iconImage = f.icon_image.value;
		var iconShadowImage = f.icon_image_shadow.value;
		var iconTitle = f.icon_title.value;
		var infoWindowContent = f.info_window_content.value;
		
		if(isNaN(width)){
			width = 100;
		}
		if(isNaN(height)){
			height = 100;
		}
		if(isNaN(zoom)){
			zoom = 13;
		}

		if(isNaN(latitude) || isNaN(longitude)){
			tinyMCEPopup.alert(tinyMCEPopup.getLang('GoogleMapsDialog.invalid_coordinates'));
		} else {
			map = new Object();
			map.width = width;
			map.height = height;
			map.longitude = longitude;
			map.latitude = latitude;
			map.zoom = zoom;
			map.hudzp = hudzp;
			map.hudmt = hudmt;
			map.huds = huds;
			map.mapstyle = mapstyle;
			
			map.iconImage = iconImage;
			map.iconShadowImage = iconShadowImage;
			map.iconTitle = iconTitle;
			map.infoWindowContent = infoWindowContent;
			
			tinyMCEPopup.editor.insertPluginGoogleMaps(map);
			tinyMCEPopup.close();
		}
	}
};

tinyMCEPopup.onInit.add(GoogleMapsDialog.init, GoogleMapsDialog);

var map=null;
var geocoder = null;
var marker = null;

function showLatLongOnMap(latitude, longitude, zoom){
	return showPointOnMap(new google.maps.LatLng(latitude, longitude), zoom);
}

function showPointOnMap(point, zoom){
	var options = new Object();
	/* zoom */
	zoom = parseInt(zoom);
	zoom = ((!isNaN(zoom))?zoom:13);
	options.zoom = zoom;
	
	/* center point */
	options.center = point;
	
	
	/* create/update the map */
	if(!map){

		/* map type */
		options.mapTypeId = google.maps.MapTypeId.ROADMAP;
		
		/* controls */
		options.navigationControl = true;
		options.mapTypeControlOptions = {
				style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR
		};
		options.mapTypeControl = true;
		options.navigationControlOptions = {
				style: google.maps.NavigationControlStyle.ZOOM_PAN
		};
		options.scaleControl = true;
		
		/* scrollwheel */
		options.scrollwheel = true;
		
		/* draggable */
		options.draggable = true;
		
		map = new google.maps.Map(document.getElementById("map"),options);
		
		/* event listeners */
		google.maps.event.addListener(map, 'zoom_changed', function() {
			document.getElementById("zoomlevel").value = map.getZoom();
		});
		google.maps.event.addListener(map, 'maptypeid_changed', function() {
			var v = 1;
			switch(map.getMapTypeId()){
				case google.maps.MapTypeId.SATELLITE:
					v = 2;
					break;

				case google.maps.MapTypeId.HYBRID:
					v = 3;
					break;

				case google.maps.MapTypeId.TERRAIN:
					v = 4;
					break;

				default:
					v = 1;
			}
			document.getElementById("mapstyle").value = v;
		});
		
	}else{
		map.setOptions(options);
	}
	/* add a marker */

	if(!marker){
		marker = new google.maps.Marker({
			position: map.getCenter(), 
			map: map,
			cursor: 'pointer',
			draggable: true,
			visible: true,
			title: tinyMCEPopup.getLang('GoogleMapsDialog.drag_icon')
		});
		google.maps.event.addListener(marker, 'drag', function() {
			document.getElementById("latitude").value = marker.getPosition().lat();
			document.getElementById("longitude").value = marker.getPosition().lng();
			document.getElementById("zoomlevel").value = map.getZoom();
		});
	}else{
		marker.setOptions({
			position: map.getCenter(), 
			map: map
		});
	}

	return map;
}

function showAddress(address){
	if(!geocoder){
		geocoder = new google.maps.Geocoder();
	}
	geocoder.geocode( { address: address}, function(results, status) {
		if (status == google.maps.GeocoderStatus.OK && results.length) {
			if (status != google.maps.GeocoderStatus.ZERO_RESULTS) {
				if (map!=null){
					var point = results[0].geometry.location;
					map = showPointOnMap(point,map.getZoom());
					document.getElementById("latitude").value = point.lat();
					document.getElementById("longitude").value = point.lng();
					document.getElementById("zoomlevel").value = map.getZoom();
				}
			}else{
				tinyMCEPopup.alert(address + ' '+ tinyMCEPopup.getLang('GoogleMapsDialog.not_found'));
			}
		} else {
			tinyMCEPopup.alert(tinyMCEPopup.getLang('GoogleMapsDialog.search_unsuccessful')+' ('+status+')');
		}
	});
}

function updateMap(){
	var latitude = parseFloat(document.getElementById("latitude").value);
	var longitude = parseFloat(document.getElementById("longitude").value);
	var zoomlevel = parseInt(document.getElementById("zoomlevel").value);
	var map;

	if(latitude && longitude && zoomlevel){
		map = showLatLongOnMap(latitude, longitude, zoomlevel);
	} else {
		map = showLatLongOnMap(39.74356851278918, -8.792168498039246, zoomlevel);
	}
	if(map){
		var options = new Object();
		switch(parseInt(document.getElementById("mapstyle").value)){
			case 2:
				options.mapTypeId = google.maps.MapTypeId.SATELLITE;
				break;

			case 3:
				options.mapTypeId = google.maps.MapTypeId.HYBRID;
				break;

			case 4:
				options.mapTypeId = google.maps.MapTypeId.TERRAIN;
				break;

			default:
				options.mapTypeId = google.maps.MapTypeId.ROADMAP;
		}
		
		switch(parseInt(document.getElementById("hudmt").value)){
			case 2:
				options.mapTypeControl=true;
				options.mapTypeControlOptions={ 
					style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR 
				};
				break;
	
			case 3:
				options.mapTypeControl=true;
				options.mapTypeControlOptions={ 
					style: google.maps.MapTypeControlStyle.DROPDOWN_MENU 
				};
				break;
	
			case 4:
				options.mapTypeControl=false;
				break;
	
			default:
				options.mapTypeControl=true;
				options.mapTypeControlOptions={ 
					style: google.maps.MapTypeControlStyle.DEFAULT 
				};
		}
		
		switch(parseInt(document.getElementById("hudzp").value)){
			case 2:
				options.navigationControl=true;
				options.navigationControlOptions={ 
						style: google.maps.NavigationControlStyle.SMALL 
				};
				break;
	
			case 3:
				options.navigationControl=true;
				options.navigationControlOptions={ 
						style: google.maps.NavigationControlStyle.ANDROID 
				};
				break;
	
			case 4:
				options.navigationControl=true;
				options.navigationControlOptions={ 
						style: google.maps.NavigationControlStyle.ZOOM_PAN 
				};
				break;
	
			case 5:
				options.navigationControl=false;
				break;
	
			default:
				options.navigationControl=true;
				options.navigationControlOptions={ 
						style: google.maps.NavigationControlStyle.DEFAULT 
				};
		}
		
		switch(parseInt(document.getElementById("huds").value)){ 
			case 2:
				options.scaleControl=false;
				break;
	
			default:
				options.scaleControl=true;
				options.scaleControlOptions={ 
						style: google.maps.ScaleControlStyle.STANDARD 
				};
		}
		
		map.setOptions(options);
	}
}
