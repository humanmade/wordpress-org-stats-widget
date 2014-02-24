# HM WP.org Stats

## Configuring
```php
// WordPress.org plugins stats
add_filter( 'hm_wporg_plugins', function () {
    return array( 'backupwordpress', 'wp-thumb', 'hm-portfolio', 'wpremote', 'menu-exporter' );;
});
```

## Contribution guidelines ##

See https://github.com/humanmade/hm-wporg-stats/blob/master/CONTRIBUTING.md