import { Database, Model } from '@vuex-orm/core'

export interface GlobalConfigInterface {
  database: Database
  sourceName?: string
}

export interface ComponentsInterface {
  Model: typeof Model
}

declare module '@vuex-orm/core' {
  // eslint-disable-next-line @typescript-eslint/no-namespace
  namespace Model {
    // Exchange source name
    const $devicesModuleSource: string
  }
}

// Re-export models types
export * from '@/lib/types'
export * from '@/lib/models/channel-controls/types'
export * from '@/lib/models/channel-properties/types'
export * from '@/lib/models/channels/types'
export * from '@/lib/models/connectors/types'
export * from '@/lib/models/connector-controls/types'
export * from '@/lib/models/device-controls/types'
export * from '@/lib/models/device-properties/types'
export * from '@/lib/models/devices/types'
export * from '@/lib/models/properties/types'
