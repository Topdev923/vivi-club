<?php
/**
 * JobClass - Job Board Web Application
 * Copyright (c) BedigitCom. All Rights Reserved
 *
 * Website: https://bedigit.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from CodeCanyon,
 * Please read the full License from here - http://codecanyon.net/licenses/standard
 */

namespace App\Helpers;


use Illuminate\Support\Str;

class UrlGen
{
	/**
	 * @param $entry
	 * @param bool $encoded
	 * @return string
	 */
	public static function postPath($entry, $encoded = false)
	{
		if (is_array($entry)) {
			$entry = ArrayHelper::toObject($entry);
		}
		
		if (isset($entry->id) && isset($entry->title)) {
			$preview = !isVerifiedPost($entry) ? '?preview=1' : '';
			
			$slug = ($encoded) ? rawurlencode($entry->slug) : $entry->slug;
			
			$path = str_replace(['{slug}', '{id}'], [$slug, $entry->id], config('routes.post'));
			$path = $path . $preview;
		} else {
			$path = '#';
		}
		
		return $path;
	}
	
	/**
	 * @param $entry
	 * @param bool $encoded
	 * @return string
	 */
	public static function postUri($entry, $encoded = false)
	{
		$uri = self::postPath($entry, $encoded);
		
		return $uri;
	}
	
	/**
	 * @param $entry
	 * @return bool|\Illuminate\Contracts\Routing\UrlGenerator|mixed|string|null
	 */
	public static function post($entry)
	{
		if (is_array($entry)) {
			$entry = ArrayHelper::toObject($entry);
		}
		
		$url = url(self::postPath($entry));
		
		return $url;
	}
	
	/**
	 * @param bool $httpError
	 * @return bool|\Illuminate\Contracts\Routing\UrlGenerator|mixed|string|null
	 */
	public static function addPost($httpError = false)
	{
		$url = (config('settings.single.publication_form_type') == '2')
			? url('create')
			: url('posts/create');
		
		return $url;
	}
	
	/**
	 * @param $entry
	 * @return false|\Illuminate\Contracts\Routing\UrlGenerator|string|null
	 */
	public static function editPost($entry)
	{
		if (is_array($entry)) {
			$entry = ArrayHelper::toObject($entry);
		}
		
		if (isset($entry->id)) {
			$url = (config('settings.single.publication_form_type') == '2')
				? url('edit/' . $entry->id)
				: url('posts/' . $entry->id . '/edit');
		} else {
			$url = '#';
		}
		
		return $url;
	}
	
	/**
	 * @param $cat
	 * @param null $except
	 * @param bool $debug
	 * @return mixed|string
	 */
	public static function getCatParentUrl($cat, $except = null, $debug = false)
	{
		if (Str::startsWith(config('routes.searchPostsByCat'), request()->segment(1) . '/')) {
			
			$catParentUrl = (isset($cat->parent) && !empty($cat->parent))
				? UrlGen::category($cat->parent, null, null, false)
				: UrlGen::category($cat, null, null, false);
			
		} else {
			
			if (is_null($except)) {
				$except = [];
				if (request()->filled('c') && request()->filled('sc')) {
					$except[] = 'sc';
				} else {
					if (request()->filled('c')) {
						$except[] = 'c';
					}
					if (request()->filled('sc')) {
						$except[] = 'sc';
					}
				}
			}
			
			if (is_string($except)) {
				$except = [$except];
			}
			
			if (!is_array($except)) {
				self::search();
			}
			
			$except = array_filter($except); // Remove empty elements
			
			if (!in_array('cf', $except)) {
				$except[] = 'cf';
			}
			
			$catParentUrl = self::search([], $except);
			
		}
		
		return $catParentUrl;
	}
	
	/**
	 * @param $entry
	 * @param null $countryCode
	 * @param null $city
	 * @param bool $findParent
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\UrlGenerator|mixed|string
	 */
	public static function category($entry, $countryCode = null, $city = null, $findParent = true)
	{
		if (!empty($city) && isset($city->id)) {
			$params = [
				'l'  => $city->id,
				'c'  => $entry->id,
			];
			
			$url = self::search(array_merge(request()->except(['page'] + array_keys($params)), $params));
			
			return $url;
		}
		
		if (empty($countryCode)) {
			$countryCode = config('country.code');
		}
		
		$countryCodePath = '';
		if (config('settings.seo.multi_countries_urls')) {
			if (!empty($countryCode)) {
				$countryCodePath = strtolower($countryCode) . '/';
			}
		}
		
		if (is_array($entry)) {
			$entry = ArrayHelper::toObject($entry);
		}
		
		if (isset($entry->slug)) {
			if ($findParent && isset($entry->parent) && !empty($entry->parent)) {
				$path = str_replace(['{countryCode}/', '{catSlug}', '{subCatSlug}'], ['', $entry->parent->slug, $entry->slug], config('routes.searchPostsBySubCat'));
			} else {
				$path = str_replace(['{countryCode}/', '{catSlug}'], ['', $entry->slug], config('routes.searchPostsByCat'));
			}
			$url = url($countryCodePath . $path);
		} else {
			$url = self::search();
		}
		
		return $url;
	}
	
	/**
	 * @param $entry
	 * @param null $countryCode
	 * @param null $cat
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\UrlGenerator|mixed|string
	 */
	public static function city($entry, $countryCode = null, $cat = null)
	{
		if (!empty($cat) && isset($cat->id)) {
			if (isset($cat->parent) && !empty($cat->parent)) {
				$params = [
					'l'  => $entry->id,
					'c'  => $cat->parent->id,
					'sc' => $cat->id,
				];
			} else {
				$params = [
					'l'  => $entry->id,
					'c'  => $cat->id,
				];
			}
			
			$url = self::search(array_merge(request()->except(['page'] + array_keys($params)), $params));
			
			return $url;
		}
		
		if (empty($countryCode)) {
			if (isset($entry->country_code) && !empty($entry->country_code)) {
				$countryCode = $entry->country_code;
			} else {
				$countryCode = config('country.code');
			}
		}
		
		$countryCodePath = '';
		if (config('settings.seo.multi_countries_urls')) {
			if (!empty($countryCode)) {
				$countryCodePath = strtolower($countryCode) . '/';
			}
		}
		
		if (is_array($entry)) {
			$entry = ArrayHelper::toObject($entry);
		}
		
		if (isset($entry->id, $entry->name)) {
			$path = str_replace(['{countryCode}/', '{city}', '{id}'], ['', $entry->slug ?? slugify($entry->name), $entry->id], config('routes.searchPostsByCity'));
			$path = $countryCodePath . $path;
			if (isFromAdminPanel()) {
				$url = dmUrl($entry->country_code, $path);
			} else {
				$url = url($path);
			}
		} else {
			$url = '#';
		}
		
		return $url;
	}
	
	/**
	 * @param $entry
	 * @param null $countryCode
	 * @return false|\Illuminate\Contracts\Routing\UrlGenerator|string|null
	 */
	public static function user($entry, $countryCode = null)
	{
		if (empty($countryCode)) {
			$countryCode = config('country.code');
		}
		
		$countryCodePath = '';
		if (config('settings.seo.multi_countries_urls')) {
			if (!empty($countryCode)) {
				$countryCodePath = strtolower($countryCode) . '/';
			}
		}
		
		if (is_array($entry)) {
			$entry = ArrayHelper::toObject($entry);
		}
		
		if (isset($entry->username) && !empty($entry->username)) {
			$path = str_replace(['{countryCode}/', '{username}'], ['', $entry->username], config('routes.searchPostsByUsername'));
			$url = url($countryCodePath . $path);
		} else {
			if (isset($entry->id)) {
				$path = str_replace(['{countryCode}/', '{id}'], ['', $entry->id], config('routes.searchPostsByUserId'));
				$url = url($countryCodePath . $path);
			} else {
				$url = '#';
			}
		}
		
		return $url;
	}
	
	/**
	 * @param $tag
	 * @param null $countryCode
	 * @return false|\Illuminate\Contracts\Routing\UrlGenerator|string|null
	 */
	public static function tag($tag, $countryCode = null)
	{
		if (empty($countryCode)) {
			$countryCode = config('country.code');
		}
		
		$countryCodePath = '';
		if (config('settings.seo.multi_countries_urls')) {
			if (!empty($countryCode)) {
				$countryCodePath = strtolower($countryCode) . '/';
			}
		}
		
		$path = str_replace(['{countryCode}/', '{tag}'], ['', $tag], config('routes.searchPostsByTag'));
		$url = url($countryCodePath . $path);
		
		return $url;
	}
	
	/**
	 * @param null $countryCode
	 * @param null $companyId
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\UrlGenerator|string
	 */
	public static function company($countryCode = null, $companyId = null)
	{
		if (empty($countryCode)) {
			$countryCode = config('country.code');
		}
		
		$countryCodePath = '';
		if (config('settings.seo.multi_countries_urls')) {
			if (!empty($countryCode)) {
				$countryCodePath = strtolower($countryCode) . '/';
			}
		}
		
		if (!empty($companyId)) {
			$path = str_replace(['{countryCode}/', '{id}'], ['', $companyId], config('routes.searchPostsByCompanyId'));
			$url = url($countryCodePath . $path);
		} else {
			$url = url($countryCodePath . config('routes.companies'));
		}
		
		return $url;
	}
	
	/**
	 * @param array $queryArr
	 * @param array $exceptArr
	 * @param bool $currentUrl
	 * @param null $countryCode
	 * @return mixed|string
	 */
	public static function search($queryArr = [], $exceptArr = [], $currentUrl = false, $countryCode = null)
	{
		if (empty($countryCode)) {
			$countryCode = config('country.code');
		}
		
		$countryCodePath = '';
		if (config('settings.seo.multi_countries_urls')) {
			if (!empty($countryCode)) {
				$countryCodePath = strtolower($countryCode) . '/';
			}
		}
		
		if ($currentUrl) {
			$url = request()->url();
		} else {
			$path = str_replace(['{countryCode}/'], [''], config('routes.search'));
			$url = $countryCodePath . $path;
		}
		
		$url = qsUrl($url, array_merge(request()->except($exceptArr + array_keys($queryArr)), $queryArr), null, false);
		
		return $url;
	}
	
	/**
	 * @param $entry
	 * @return false|\Illuminate\Contracts\Routing\UrlGenerator|string|null
	 */
	public static function page($entry)
	{
		if (is_array($entry)) {
			$entry = ArrayHelper::toObject($entry);
		}
		
		if (isset($entry->slug)) {
			$path = str_replace(['{slug}'], [$entry->slug], config('routes.pageBySlug'));
			$url = url($path);
		} else {
			$url = '#';
		}
		
		return $url;
	}
	
	/**
	 * @param null $countryCode
	 * @return bool|\Illuminate\Contracts\Routing\UrlGenerator|mixed|string|null
	 */
	public static function sitemap($countryCode = null)
	{
		if (empty($countryCode)) {
			$countryCode = config('country.code');
		}
		
		$countryCodePath = '';
		if (config('settings.seo.multi_countries_urls')) {
			if (!empty($countryCode)) {
				$countryCodePath = strtolower($countryCode) . '/';
			}
		}
		
		$path = str_replace(['{countryCode}/'], [''], config('routes.sitemap'));
		$url = url($countryCodePath . $path);
		
		return $url;
	}
	
	public static function countries()
	{
		return url(config('routes.countries'));
	}
	
	public static function contact()
	{
		return url(config('routes.contact'));
	}
	
	public static function pricing()
	{
		return url(config('routes.pricing'));
	}
	
	public static function loginPath()
	{
		return config('routes.login');
	}
	
	public static function logoutPath()
	{
		return config('routes.logout');
	}
	
	public static function registerPath()
	{
		return config('routes.register');
	}
	
	public static function login()
	{
		return url(self::loginPath());
	}
	
	public static function logout()
	{
		return url(self::logoutPath());
	}
	
	public static function register()
	{
		return url(self::registerPath());
	}
}
