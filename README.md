# Content Blocks

Allow clients to update content blocks directly from the layouts/pages/partials that use them.

![](https://github.com/Flynsarmy/wn-contentblocks-plugin/blob/master/assets/images/marketplace/content-tab.png?raw=true)

## Description

It's cumbersome to go to the *CMS - Content* section, create a content block, get its filename then add the block to your layout/page/partial. Then every time you want to edit the block you need to head back to the *Content* area and find the correct one to update. 

With this plugin you can simply reference your content blocks in the markup area and they'll appear in a new *Content* tab right there on the layout/page/partial you're currently working on. Magic!

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
