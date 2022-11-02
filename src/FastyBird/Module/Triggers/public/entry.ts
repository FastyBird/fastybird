import { ModuleSource } from '@fastybird/metadata'
import { Plugin } from '@vuex-orm/core/dist/src/plugins/use'

import Trigger from '@/lib/models/triggers/Trigger'
import triggers from '@/lib/models/triggers'
import TriggerControl from '@/lib/models/trigger-controls/TriggerControl'
import triggerControls from '@/lib/models/trigger-controls'
import Condition from '@/lib/models/conditions/Condition'
import conditions from '@/lib/models/conditions'
import Action from '@/lib/models/actions/Action'
import actions from '@/lib/models/actions'
import Notification from '@/lib/models/notifications/Notification'
import notifications from '@/lib/models/notifications'

// Import typing
import { ComponentsInterface, GlobalConfigInterface } from '@/types/triggers-module'

// install function executed by VuexORM.use()
const install: Plugin = function installVuexOrmWamp(components: ComponentsInterface, config: GlobalConfigInterface) {
  if (typeof config.sourceName !== 'undefined') {
    // @ts-ignore
    components.Model.$triggersModuleSource = config.sourceName
  } else {
    // @ts-ignore
    components.Model.$triggersModuleSource = ModuleSource.MODULE_TRIGGERS_SOURCE
  }

  config.database.register(Trigger, triggers)
  config.database.register(TriggerControl, triggerControls)
  config.database.register(Condition, conditions)
  config.database.register(Action, actions)
  config.database.register(Notification, notifications)
}

// Create module definition for VuexORM.use()
const plugin = {
  install,
}

// Default export is library as a whole, registered via VuexORM.use()
export default plugin

// Export model classes
export {
  Action,
  Condition,
  Notification,
  Trigger,
  TriggerControl,
}

export * from '@/lib/errors'

// Re-export plugin typing
export * from '@/types/triggers-module'
