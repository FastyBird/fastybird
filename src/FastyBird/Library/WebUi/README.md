# FastyBird web-ui theme library

[![Latest stable](https://badgen.net/npm/v/@fastybird/web-ui-theme?cache=300&style=flast-square)](https://www.npmjs.com/package/@fastybird/web-ui-theme)
[![Downloads total](https://badgen.net/npm/dt/@fastybird/web-ui-theme?cache=300&style=flast-square)](https://www.npmjs.com/package/@fastybird/web-ui-theme)
[![Licence](https://badgen.net/npm/license/@fastybird/web-ui-theme?cache=300&style=flast-square)](https://github.com/FastyBird/web-ui-theme/blob/master/LICENSE.md)
![Types](https://badgen.net/npm/types/@fastybird/web-ui-theme?cache=300&style=flast-square)

## What is FastyBird web-ui theme library?

This library is a collection of [Vue](https://vuejs.org) framework components used for [FastyBird](https://www.fastybird.com) web application user interfaces.

[FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) web-ui theme library is an [Apache2 licensed](http://www.apache.org/licenses/LICENSE-2.0) distributed library, developed in [Typescript](https://www.typescriptlang.org) with [Vue](https://vuejs.org) and [Nuxt](https://nuxtjs.org).

### Features:

- Web user interface elements like modal windows, buttons, alerts, switches etc.
- Forms elements with support for pre and post-fixes
- Page layout components for desktop and mobile views
- Customizable styles

## Requirements

Library is tested against [ECMAScript 6](https://www.w3schools.com/JS/js_es6.asp)

## Installation

The best way to install **@fastybird/web-ui-theme** is using [Yarn](https://yarnpkg.com/):

```sh
yarn add @fastybird/web-ui-theme
```

or if you prefer npm:

```sh
npm install @fastybird/web-ui-theme
```

## Setup in your application

Register Vue plugin:

```js
import Vue from 'vue'
import FastyBirdWebUI from '@fastybird/web-ui-theme'

Vue.use(FastyBirdWebUI)
```

## Usage

In you component you could use registered visual, form and layouts components:

```vue
<template>
    <div>
        Your fancy content here

        <fb-ui-button
          href="/new/page"
          variant="primary"
          size="md"
        >
            Click here
        </fb-ui-button>
    </div>
</template>
```

## Typescript setup

Add the types to your `"types"` array in **tsconfig.json**.

```json
{
  "compilerOptions": {
    "types": [
      "@fastybird/web-ui-theme"
    ]
  }
}
```

## Documentation

Learn how to use ui components in [documentation](https://storybook.fastybird.com/).

## Feedback

Use the [issue tracker](https://github.com/FastyBird/web-ui-theme/issues) for bugs or [mail](mailto:code@fastybird.com) or [Tweet](https://twitter.com/fastybird) us for any idea that can improve the project.

Thank you for testing, reporting and contributing.

## Changelog

For release info check [release page](https://github.com/FastyBird/web-ui-theme/releases)

## Maintainers

<table>
	<tbody>
		<tr>
			<td align="center">
				<a href="https://github.com/akadlec">
					<img width="80" height="80" src="https://avatars3.githubusercontent.com/u/1866672?s=460&amp;v=4">
				</a>
				<br>
				<a href="https://github.com/akadlec">Adam Kadlec</a>
			</td>
		</tr>
	</tbody>
</table>

***
Homepage [https://www.fastybird.com](https://www.fastybird.com) and repository [https://github.com/FastyBird/web-ui-theme](https://github.com/FastyBird/web-ui-theme).
