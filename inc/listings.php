<?php

// Exit if accessed directly
if ( ! defined('ABSPATH') ) {
	exit;
}

if (!function_exists('bldm_display_all_listings')) {
	function bldm_display_all_listings($atts){
		
		if( !ini_get('allow_url_fopen') ) {
			return '<p>Please enable "allow_url_fopen" from server to make the plugin work correctly.</p>';
		}
		
		$render_html = '';
		if(isset($_GET['lid'])){
			$render_html = bldm_display_single_listing();
			return $render_html;
		}
		else{
			global $bldm_plugin_url;
			global $bldm_listings_url;
			
			if(!$bldm_listings_url){ return '<p>The Buildium URL is blank. Please contact site owner.</p>'; }
			
			$last_char = substr($bldm_listings_url, -1);
			if($last_char == '/'){
				$bldm_listings_url = substr($bldm_listings_url, 0, -1);
			}
			
			$render_html .= '<div class="main-listings-page" style="width: 100%; max-width: 100%;">';
			
			$url = $bldm_listings_url.'/Resident/public/rentals?hidenav=true';
			
			if(isset($_POST['fltr-submt'])){
				$params = '';
				$params_before = '';
				if(isset($_POST['filters'])){
					foreach($_POST['filters'] as $fltr_key=>$fltr_val){
						$fltr_key = sanitize_text_field($fltr_key);
						$fltr_val = sanitize_text_field($fltr_val);
						if($fltr_val){
							$params .= '&' . $fltr_key . '=' . urlencode($fltr_val);
						}
					}
				}
				$url = $url.$params;
			}
			
			$html = new simple_html_dom();
			$html->load_file($url);
			$listings = array();
			$db = array();
			
			$render_html .= '<div class="listing-filters">';
			
			$listing_filters = $html->find('.rentals-filter', 0);
			if($listing_filters){
				$location = $listing_filters->find('.js-rentals__search-input', 0);
				$rent_min = $listing_filters->find('#rentFromFilter', 0);
				$rent_max = $listing_filters->find('#rentToFilter', 0);
				$filters_bedrooms = $listing_filters->find('#bedroomFilter', 0);
				$filters_bathrooms = $listing_filters->find('#bathroomFilter', 0);
				$filters_type = $listing_filters->find('#propertyTypeFilter', 0);
			}
			$render_html .= '<form method="post">';
			
			// Filters
			$searched_beds = $searched_baths = $searched_rent_min = $searched_rent_max = 0;
			$searched_loc = $searched_type = '';
			if($location){
				if(isset($_POST['filters']['location'])){
					$searched_loc = sanitize_text_field($_POST['filters']['location']);
					$render_html .= '<input type="text" name="filters[location]" value="'.$searched_loc.'" placeholder="'.$location->{'placeholder'}.'">';
				} else{
					$render_html .= '<input type="text" name="filters[location]" placeholder="'.$location->{'placeholder'}.'">';
				}
			}
			
			if($rent_min){
				if(isset($_POST['filters']['rent-min'])){
					$searched_rent_min = sanitize_text_field($_POST['filters']['rent-min']);
					$render_html .= '<input type="number" name="filters[rent-min]" value="'.$searched_rent_min.'" step="100" min="0" placeholder="$ Min Rent">';
				} else{
					$render_html .= '<input type="number" name="filters[rent-min]" step="100" min="0" placeholder="$ Min Rent">';
				}
			}
			
			if($rent_max){
				if(isset($_POST['filters']['rent-max'])){
					$searched_rent_max = sanitize_text_field($_POST['filters']['rent-max']);
					$render_html .= '<input type="number" name="filters[rent-max]" value="'.$searched_rent_max.'" step="100" min="0" placeholder="$ Max Rent">';
				} else{
					$render_html .= '<input type="number" name="filters[rent-max]" step="100" min="0" placeholder="$ Max Rent">';
				}
			}
			
			if($filters_bedrooms){
				$correct_beds = str_replace("0+", "Beds", stripslashes($filters_bedrooms->innertext));
				if(isset($_POST['filters']['bedrooms'])){
					$searched_beds = $selected = sanitize_text_field($_POST['filters']['bedrooms']);
					$str_to_replace = 'value="'.$selected.'"';
					$str_to_replace_by = 'value="'.$selected.'" selected="selected"';
					$render_html .= '<select name="filters[bedrooms]">'.str_replace($str_to_replace,$str_to_replace_by,$correct_beds).'</select>';
				} else{
					$render_html .= '<select name="filters[bedrooms]">'.$correct_beds.'</select>';
				}
			}
			
			if($filters_bathrooms){
				$correct_baths = str_replace("0+", "Baths", stripslashes($filters_bathrooms->innertext));
				if(isset($_POST['filters']['bathrooms'])){
					$searched_baths = $selected = sanitize_text_field($_POST['filters']['bathrooms']);
					$str_to_replace = 'value="'.$selected.'"';
					$str_to_replace_by = 'value="'.$selected.'" selected="selected"';
					$render_html .= '<select name="filters[bathrooms]">'.str_replace($str_to_replace,$str_to_replace_by,$correct_baths).'</select>';
				} else{
					$render_html .= '<select name="filters[bathrooms]">'.$correct_baths.'</select>';
				}
			}
			
			if($filters_type){
				$correct_type = stripslashes($filters_type->innertext);
				if(isset($_POST['filters']['propertyTypeFilter'])){
					$searched_type = $selected = sanitize_text_field($_POST['filters']['propertyTypeFilter']);
					$str_to_replace = 'value="'.$selected.'"';
					$str_to_replace_by = 'value="'.$selected.'" selected="selected"';
					$render_html .= '<select name="filters[propertyTypeFilter]">'.str_replace($str_to_replace,$str_to_replace_by,$correct_type).'</select>';
				} else{
					$render_html .= '<select name="filters[propertyTypeFilter]">'.$correct_type.'</select>';
				}
			}
			
			$render_html .= '<input type="submit" value="SEARCH" name="fltr-submt">';
			
			$render_html .= '</form></div>';
			
			// All listings in columns
			$render_html .= '<div class="all-listings section_wrapper mcb-section-inner">';
			$listing_items = $html->find('a.featured-listing');
			
			if($listing_items){
				$listings_fnd = 0;
				foreach ($listing_items as $listing) {
					
					$list_beds = $listing->{'data-bedrooms'};
					$list_baths = $listing->{'data-bathrooms'};
					$list_rent = $listing->{'data-rent'};
					$list_type = $listing->{'data-type'};
					$list_location = $listing->{'data-location'};
					
					if((int)$searched_beds){
						if($list_beds < (int)$searched_beds){
							continue;
						}
					}
					if((int)$searched_baths){
						if($list_baths < (int)$searched_baths){
							continue;
						}
					}
					if((int)$searched_rent_min){
						if($list_rent < (int)$searched_rent_min){
							continue;
						}
					}
					if((int)$searched_rent_max){
						if($list_rent > (int)$searched_rent_max){
							continue;
						}
					}
					
					if($searched_type){
						if($list_type != $searched_type){
							continue;
						}
					}
					
					if(!empty($searched_loc)){
						if(strpos(strtolower($list_location), strtolower($searched_loc)) === false){
							continue;
						}
					}
					
					$listing_ID = '';
					$list_url = $listing->{'href'};
					if($list_url){
						$list_url_part = explode('?hidenav', $list_url);
						$list_url = $list_url_part[0];
						$pos = strrpos($list_url, '/');
						if($pos){
							$listing_ID = substr($list_url, $pos + 1);
						}
					}
					
					$listing_Img = '';
					$listing_Img_obj = $listing->find('.featured-listing__image-container img', 0);
					if($listing_Img_obj){
						$listing_Img = $listing_Img_obj->{'src'};
						$listing_Img = $bldm_listings_url . $listing_Img;
					}
					
					$listing_rent = '';
					$listing_rent = $listing->{'data-rent'};
					
					$listing_beds = '';
					$listing_beds = $listing->{'data-bedrooms'};
					
					$listing_baths = '';
					$listing_baths = $listing->{'data-bathrooms'};
					
					$listing_ttl = $listing_address = '';
					$listing_content = $listing->find('.featured-listing__content', 0);
					if($listing_content){
						$listing_address_obj = $listing_content->find('.featured-listing__address', 0);
						if($listing_address_obj){
							$listing_address = $listing_address_obj->innertext;
						}
						$listing_ttl_obj = $listing_content->find('.featured-listing__title', 0);
						if($listing_ttl_obj){
							$listing_ttl = $listing_ttl_obj->innertext;
						}
					}
					
					if($listing_ID){
						$listing_Apply_Link = $bldm_listings_url . '/Resident/rental-application/?listingId='.$listing_ID.'&hidenav=true';
					}
					
					$render_html .= '<div class="listing-item column mcb-column one-third">
						<a href="?lid='.$listing_ID.'">
						<div class="list-img">
							<img src="'.$listing_Img.'">
							<span class="rent-price">$'.$listing_rent.'</span>
						</div></a>
						<div class="details">
							<h6 class="lstng_ttl">'.$listing_ttl.'</h6>
							<p class="address">'.$listing_address.'</p>
							<p><img class="bedimg" src="'.$bldm_plugin_url.'images/sleep.png"><span class="beds">'.$listing_beds.' Bed </span> <img class="bathimg" src="'.$bldm_plugin_url.'images/bathtub.png"><span class="baths">'.$listing_baths.' Bath</span></p>
							<div class="btns">
								<a href="?lid='.$listing_ID.'">Details</a>
								<a href="'.$listing_Apply_Link.'" target="_blank">Apply</a>
							</div>
						</div>
					</div>';
					
					$listings_fnd = 1;
				}
				if(!$listings_fnd){
					$render_html .= '<div class="no-listings"><p>No vacancies found matching your search criteria. Please select other filters.</p></div>';
				}
			} else{
				$render_html .= '<div class="no-listings"><p>No vacancies found matching your search criteria. Please select other filters.</p></div>';
			}
			$render_html .= '</div></div>';
			
			return $render_html;
		
		}
		
	}
}
