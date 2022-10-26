<?php
$cache_location_exists = [];
$css_cache_location = [];
$is_between_location_cache = [];
$astra_header_cache_id = "my_cache_elementor_locations_v3";
$starting_time_of_me = microtime(true);
function cache_location_begin($location,$location_manager){
	if(!class_exists('ElementorPro\Modules\ThemeBuilder\Module')){return;}
	if(ElementorPro\Modules\ThemeBuilder\Module::is_preview()){return;}
	global $astra_header_cache_id;
	global $starting_time_of_me;
//	print microtime(true) - $starting_time_of_me;
	$starting_time_of_me = microtime(true);
    $inCache = get_transient($astra_header_cache_id.$location);
    if(!empty($inCache)){
		$css = get_transient($astra_header_cache_id.$location.'css');
		foreach($css as $post_id){
			$css_file = Elementor\Core\Files\CSS\Post::create( $post_id );
			$css_file->enqueue();
		}
		
		global $cache_location_exists;
		$cache_location_exists[$location] = true;
        print $inCache;
		$documents = ElementorPro\Modules\ThemeBuilder\Module::instance()->get_conditions_manager()->get_documents_for_location( $location );
		foreach($documents as $key=>$document){
			$location_manager->set_is_printed($location,$key);
		}
		do_action( "elementor/theme/after_do_".$location, $location_manager );
    }else{
		global $is_between_location_cache;
		$is_between_location_cache[$location] = true;
		ob_start();
    }
}
function cache_location_end($location,$location_manager){
	if(!class_exists('ElementorPro\Modules\ThemeBuilder\Module')){return;}
	if(ElementorPro\Modules\ThemeBuilder\Module::is_preview()){return;}
	global $cache_location_exists;
	global $astra_header_cache_id;
	if(empty($cache_location_exists[$location])){
		$content = ob_get_contents();
	    ob_end_clean();
		if(empty($_COOKIE["woocommerce_items_in_cart"])){
			set_transient($astra_header_cache_id.$location,$content,60*15);
		}
		print $content;
		global $css_cache_location;
		if(!empty($css_cache_location[$location])){
			if(empty($_COOKIE["woocommerce_items_in_cart"])){
				set_transient($astra_header_cache_id.$location.'css',$css_cache_location[$location],60*15);
			}
		}
	}
	global $is_between_location_cache;
	$is_between_location_cache[$location] = false;
}
if(!empty($_GET['header_cache']) || true){
add_action('elementor/theme/before_do_header', function($location_manager){return cache_location_begin('header',$location_manager);},1);
add_action('elementor/theme/after_do_header', function($location_manager){return cache_location_end('header',$location_manager);},PHP_INT_MAX);
add_action('elementor/theme/before_do_footer', function($location_manager){return cache_location_begin('footer',$location_manager);},1);
add_action('elementor/theme/after_do_footer', function($location_manager){return cache_location_end('footer',$location_manager);},PHP_INT_MAX);
add_action('elementor/document/after_save',function(){
	global $astra_header_cache_id;
	$locations = ['header','footer'];
	foreach($locations as $location){
		set_transient($astra_header_cache_id.$location.'css',null,1);
		set_transient($astra_header_cache_id.$location,null,1);
	}
},100);	
add_filter('elementor/documents/get/post_id', function($post_id){
	global $css_cache_location;
	global $is_between_location_cache;
	foreach($is_between_location_cache as $location=>$enabled){
		if(!empty($enabled)){
			$css_cache_location[$location][$post_id] = $post_id;
		}
	}
	return $post_id;
},1,1);
}