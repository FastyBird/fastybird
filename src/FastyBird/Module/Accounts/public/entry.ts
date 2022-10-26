import { ModuleSource } from '@fastybird/metadata'
import { Plugin } from '@vuex-orm/core/dist/src/plugins/use'

import Account from '@/lib/models/accounts/Account'
import accounts from '@/lib/models/accounts'
import Email from '@/lib/models/emails/Email'
import emails from '@/lib/models/emails'
import Identity from '@/lib/models/identities/Identity'
import identities from '@/lib/models/identities'
import Role from '@/lib/models/roles/Role'
import roles from '@/lib/models/roles'
import RoleAccount from '@/lib/models/roles-accounts/RoleAccount'

// Import typing
import { ComponentsInterface, GlobalConfigInterface } from '@/types/accounts-module'

// install function executed by VuexORM.use()
const install: Plugin = function installVuexOrmWamp(components: ComponentsInterface, config: GlobalConfigInterface) {
  if (typeof config.sourceName !== 'undefined') {
    // @ts-ignore
    components.Model.$accountsModuleSource = config.sourceName
  } else {
    // @ts-ignore
    components.Model.$accountsModuleSource = ModuleSource.MODULE_ACCOUNTS_SOURCE
  }

  config.database.register(Account, accounts)
  config.database.register(Email, emails)
  config.database.register(Identity, identities)
  config.database.register(Role, roles)
  config.database.register(RoleAccount)
}

// Create module definition for VuexORM.use()
const plugin = {
  install,
}

// Default export is library as a whole, registered via VuexORM.use()
export default plugin

// Export model classes
export {
  Account,
  Email,
  Identity,
  Role,
  RoleAccount,
}

export * from '@/lib/errors'

// Re-export plugin typing
export * from '@/types/accounts-module'
