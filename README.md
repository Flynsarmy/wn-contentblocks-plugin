# Content Blocks

Allow clients to update content blocks directly from the layouts/pages/partials that use them.

![](https://github.com/Flynsarmy/wn-contentblocks-plugin/blob/master/assets/images/marketplace/content-tab.png?raw=true)

## Installation

* `git clone` into */plugins/flynsarmy/contentblocks*

## Usage

Simply add a `{% content 'myblock.htm' %}` to your layout/page/partial markup and save. The content block will appear in the *Content* tab.

* HTML, Markdown and Text content blocks are all supported.
* Saving your layout/page/partial will automatically create content blocks that don't already exist.
* If you pass variables to your content blocks, the available variables will be displayed next to that block on the *Content* tab.

## Limitations

* You'll need to close/re-open the layout/page/partial tab in CMS area when adding/removing content blocks in the markup section. Just save, close then re-open the tab.

## Future Improvements

* Add CMS permissions to hide all fields clients shouldn't have access to - so they can just go to CMS - Pages - Homepage and only the content blocks will appear for them.