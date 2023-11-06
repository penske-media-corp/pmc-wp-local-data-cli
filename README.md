# PMC WP Local Data CLI

WP-CLI commands used to trim a production database backup to a reasonable size,
for use in local-development environments.

## Introduction

This plugin provides a framework for reducing the size of a production database
backup to a size that is suitable for local development. It includes handlers
for both built-in WordPress data structures as well as those found in Core Tech. 
Individual themes can provide their own handlers for brand-specific code.
