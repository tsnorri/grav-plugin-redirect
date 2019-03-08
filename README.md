# Redirect Plugin

The **Redirect** Plugin is for [Grav CMS](http://github.com/getgrav/grav). It can be used to provide custom redirects with specific HTTP status codes.

## Installation

Installing the Redirect plugin can be done in one of two ways. The GPM (Grav Package Manager) installation method enables you to quickly and easily install the plugin with a simple terminal command, while the manual method enables you to do so via a zip file.

### GPM Installation (Preferred)

The simplest way to install this plugin is via the [Grav Package Manager (GPM)](http://learn.getgrav.org/advanced/grav-gpm) through your system's terminal (also called the command line).  From the root of your Grav install type:

    bin/gpm install redirect

This will install the Redirect plugin into your `/user/plugins` directory within Grav. Its files can be found under `/your/site/grav/user/plugins/redirect`.

### Manual Installation

To install this plugin, just download the zip version of this repository and unzip it under `/your/site/grav/user/plugins`. Then, rename the folder to `redirect`. You can find these files on [GitHub](https://github.com/tsnorri/grav-plugin-redirect) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/redirect
	
> NOTE: This plugin is a modular component for Grav which requires [Grav](http://github.com/getgrav/grav) and the [Error](https://github.com/getgrav/grav-plugin-error) and [Problems](https://github.com/getgrav/grav-plugin-problems) to operate.

### Admin Plugin

If you use the admin plugin, you can install directly through the admin plugin by browsing the `Plugins` tab and clicking on the `Add` button.

## Configuration

Before configuring this plugin, you should copy the `user/plugins/redirect/redirect.yaml` to `user/config/plugins/redirect.yaml` and only edit that copy.

Here is the default configuration:

```yaml
enabled: true
```

Note that if you use the admin plugin, a file with your configuration, and named redirect.yaml will be saved in the `user/config/plugins/` folder once the configuration is saved in the admin.

## Usage

Use the admin plugin or place the redirections to `user/config/plugins/redirect.yaml`. The redirections will be considered before the Error plugin is activated. Exact matches will be attempted first. If an exact match does not exist, a check is performed for each of the paths to determine, if the path is a prefix of the URL.

Here is an example configuration:

```yaml
enabled: true
redirects:
  -
    path: /en/a-path-or-prefix-with-the-language-code
    destination: /a-destination-path-or-prefix-without-the-language-code
    statusCode: '301'
```
