<p align="center">This addon enables Mollie iDeal payments on your Lunar storefront.</p>

## Alpha Release

This addon is currently in Alpha, whilst every step is taken to ensure this is working as intended, it will not be considered out of Alpha until more tests have been added and proved.

## Installation

1. Install this package via composer:

```bash
composer require pixelpillow/laravel-mollie
```

2. Publish the config file:

```bash
php artisan vendor:publish --provider="Pixelpillow\LunarMollie\MolliePaymentsServiceProvider"
```

3. Add your Mollie API key to your `.env` file:

```bash
MOLLIE_KEY=your-api-key
```
