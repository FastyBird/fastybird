import { InjectionKey } from 'vue';
import { IAccountModuleConfiguration, IAccountModuleMeta } from '@/types';

export const metaKey: InjectionKey<IAccountModuleMeta> = Symbol('accounts-module_meta');
export const configurationKey: InjectionKey<IAccountModuleConfiguration> = Symbol('accounts-module_configuration');
