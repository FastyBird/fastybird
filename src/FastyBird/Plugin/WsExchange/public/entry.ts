import {App} from 'vue'

// Import library
import {Client} from '@/lib/Client'
import {Logger} from '@/lib/Logger'
import {key, useWsExchangeClient} from '@/lib/useWsExchangeClient'
import {InstallFunction, PluginOptions} from '@/types/ws-exchange-plugin'

export const WampClientDefaultOptions = {
    autoReestablish: true,
    autoCloseTimeout: -1,
    debug: false,
}

export function createWsExchangeClient(): InstallFunction {
    const plugin: InstallFunction = {
        install(app: App, options: PluginOptions) {
            if (this.installed) {
                return;
            }
            this.installed = true;

            const pluginOptions = {...WampClientDefaultOptions, ...options};

            const wampClient = new Client(pluginOptions.wsuri as string, new Logger(pluginOptions.debug));

            app.provide(key, wampClient)
        },
    };

    return plugin;
}

export {Client, useWsExchangeClient};

export * from '@/types/ws-exchange-plugin';
