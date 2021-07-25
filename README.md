# Rabble Preview Bundle
Adds a preview to the content section when editing or creating a page in the content bundle.

# Installation
Install the bundle by running
```sh
composer require rabble/preview-bundle
```

Add the following class to your `config/bundles.php` file:
```php
return [
    ...
    Rabble\PreviewBundle\RabblePreviewBundle::class => ['all' => true],
]
```
