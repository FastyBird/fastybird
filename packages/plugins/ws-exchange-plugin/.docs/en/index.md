# Usage in user interface

## Setup in your application

Add it as a plugin to your app:

```js
import { createApp } from 'vue'
import VueWamp from '@fastybird/ws-exchange-plugin'

const app = createApp(...)

const options = {
    wsuri: 'ws://your.socker.server.com:1234'
}

app.use(VueWamp, options)
```

Or, if you are using Typescript:

```js
import { createApp } from 'vue'
import VueWamp, { PluginOptions } from '@fastybird/ws-exchange-plugin'

const app = createApp(...)

const options: PluginOptions = {
    wsuri: 'ws://your.socker.server.com:1234'
}

app.use(VueWamp, options)
```

#### Options:

- `wsuri` - is required option and with this field is representing your wamp server address
- `debug` - default is `false` and this option is to enable or disable console log of wamp events

## Usage

In you component you could establish connection and subscribe to wamp events:

```vue
<script>
import { useWsExchangeClient } from '@fastybird/ws-exchange-plugin'

export default {
    setup() {
        // Get wamp client interface
        const { open, close, status, client } = useWsExchangeClient();

        onMounted(() => {
            open()

            client.subscribe(
              '/topic/path',
              (data) => {
                console.log(data) // Data sent by server to the topic
              },
            )
        })

        onBeforeUnmount(() => {
            close()
        })

        // Make it available inside methods
        return { status, client }
    },

    methods: {
        clickButton: (data) => {
            // Publish to specific topic
            this.client.publish('/topic/path', 'Hello world')
        },

        clickOtherButton: (data) => {
            // RPC
            this.client.call('/topic/path', 'Hello world')
                .then(() => {
                  console.log('Call was successful')
                })
                .catch(() => {
                  console.log('Something went wrong')
                })
        },
    }
}
</script>
```

## Typescript setup

Add the types to your `"types"` array in **tsconfig.json**.

```json
{
  "compilerOptions": {
    "types": [
      "@fastybird/ws-exchange-plugin"
    ]
  }
}
```

***
Homepage [https://www.fastybird.com](https://www.fastybird.com) and
repository [https://github.com/FastyBird/ws-exchange-plugin](https://github.com/FastyBird/ws-exchange-plugin).
