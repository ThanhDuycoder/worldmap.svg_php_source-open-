<?php
declare(strict_types=1);

// External API
const RESTCOUNTRIES_BASE_URL = 'https://restcountries.com/v3.1';
// Minimal fields for list/search + mapping name -> cca2
const RESTCOUNTRIES_ALL_FIELDS = 'name,cca2,capital,region,subregion';
// Full detail fields (alpha/{code})
const RESTCOUNTRIES_ALPHA_FIELDS = 'name,cca2,capital,region,subregion,latlng,population,currencies,languages,flags,maps,timezones';

// Cache
const CACHE_DIR = __DIR__ . '/../cache';
const COUNTRIES_CACHE_FILE = CACHE_DIR . '/countries.json';
const COUNTRIES_CACHE_TTL_SECONDS = 60 * 60 * 12; // 12 hours

