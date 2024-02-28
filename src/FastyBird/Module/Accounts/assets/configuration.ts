import { InjectionKey } from 'vue';
import { IAccountsModuleConfiguration, IAccountsModuleMeta } from './types';

export const metaKey: InjectionKey<IAccountsModuleMeta> = Symbol('accounts-module_meta');
export const configurationKey: InjectionKey<IAccountsModuleConfiguration> = Symbol('accounts-module_configuration');
