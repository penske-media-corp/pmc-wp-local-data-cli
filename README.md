# PMC WP Local Data CLI

WP-CLI commands used to trim a production database backup to a reasonable size,
for use in local-development environments.

## Introduction

This plugin provides a framework for reducing the size of a production database
backup to a size that is suitable for local development. It includes handlers
for both built-in WordPress data structures as well as those found in Core Tech. 
Individual themes can provide their own handlers for brand-specific code.

As this plugin is built to support PMC's architecture, it depends on parts of 
our that are not represented in this plugin. We share it publicly to serve as a
guide.

## Overview

The plugin is organized into two broad categories. The first portion deals with 
running the trimming process and otherwise making the database suitable for use 
in local development. The second part of the plugin implements the queries used
to identify what content is retained in the final backup.

Included in the first aspect is the logic for collecting post IDs that are to
remain in the final backup, as well as the processing that removes post objects
that are not to be retained. A temporary table is created, to which IDs that are
to be retained are added. Additionally, a mechanism is provided for collecting
related IDs, such as for a post's thumbnail or other dependencies. The plugin
also provides hooks that execute before and after the database is processed, so
that sensitive information can be removed. For example, API keys can be removed
before the database is processed so that API calls are not made when posts are
deleted; after the database is trimmed, sensitive information can be removed
from the content that remains, such as commenter email addresses.

The second element of the plugin is contained in the `classes/query-args` 
subdirectory. Each class therein extends a base class and specifies the 
`WP_Query` arguments used to collect post IDs to retain. Additionally, each 
class can provide logic to gather dependent IDs, ensuring that the trimmed
database contains posts that are fully functional and resemble their production
equivalents as faithfully as possible.

To ensure that the trimmed database is as small as possible, the plugin uses
`wp_delete_post()` to remove unneeded posts; doing so allows WordPress to remove
revisions, postmeta, term associations, etc. without the plugin needing to 
account for all of a post's associations across WordPress's tables.

In some cases, it may not be possible to remove a post due to protections in
place in other parts of a site's codebase. For instance, if special pages are
created programmatically, they may be protected from deletion. In this 
situation, an infinite loop can result as the plugin attempts to remove the
protected IDs. To prevent this, the plugin will terminate the trimming process
if its iterations exceed 125% of what was expected.

## Supporting Brand-Specific Features

This plugin provides handlers for both post types that are native to WordPress,
as well as those found in Core Tech. Any post type that is registered in a 
brand's theme must have a handler added to the theme or its objects will be 
removed by the trimming process. The plugin provides a filter that allows a 
brand theme to register its instances of the `PMC\WP_Local_Data_CLI\Query_Args` 
class.

Consider this example:

```php
<?php

use PMC\WP_Local_Data_CLI\Init;

if ( class_exists( Init::class, false ) ) {
    add_filter(
        'pmc_wp_cli_local_data_query_args_instances',
        static function ( array $instances ): array {
            return array_merge(
                $instances,
                [
                    new Brand_Feature\Handler(),
                ]
            );
        }
    );
}
```
 
In this example, `Brand_Feature\Handler()` extends the `Query_Args` class 
provided by this plugin. The `pmc_wp_cli_local_data_query_args_instances` filter
expects an array of instances of the classes that extend the `Query_Args` class.
