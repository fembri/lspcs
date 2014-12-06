lspcs
=====

Laravel Session patch to support concurrent request.


### Installation
Open your composer.json file and add the new required package.

```
"fembri/lspcs" : "1.0.*"
```
Next, open your terminal and run `composer update`.
After composer updated, replace laravel Illuminate\Session\SessionServiceProvider in `app/config/app.php` with:

```
'Lspcs\SessionServiceProvider',
```
Done.

### How it works
Laravel session mechanism save its data on the end of a single request, so when you modify your session data in a request laravel don't really save it to the storage. We simply modified it, read directly from storage before you get the data or before you update the data, and then write it to the storage. Sure it would slow down the performance, but you can activated its persistent mode only when you need it.

### Usage
```
Session::persistentMode(true);
```
