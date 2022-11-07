import { defineStore } from 'pinia'
import axios from 'axios'
import JsonaService from 'jsona'
import Ajv from 'ajv'
import { v4 as uuid } from 'uuid'
import get from 'lodash/get'

import exchangeEntitySchema from '@fastybird/metadata-library/resources/schemas/modules/devices-module/entity.channel.json'
import {
  ChannelEntity as ExchangeEntity,
  DevicesModuleRoutes as RoutingKeys,
  ModulePrefix,
} from '@fastybird/metadata-library'

import { ApiError } from '@/lib/errors'
import {
  JsonApiJsonPropertiesMapper,
  JsonApiModelPropertiesMapper,
} from '@/lib/jsonapi'
import {
  useChannelControls,
  useChannelProperties,
  useDevices,
} from '@/lib/models'
import {
  IChannelControlResponseModel,
  IChannelPropertyResponseModel,
  IPlainRelation,
} from '@/lib/models/types'

import {
  IChannelsState,
  IChannelsActions,
  IChannelsGetters,
  IChannel,
  IChannelsAddActionPayload,
  IChannelsEditActionPayload,
  IChannelRecordFactoryPayload,
  IChannelResponseModel,
  IChannelResponseJson,
  IChannelsResponseJson,
  IChannelsGetActionPayload,
  IChannelsFetchActionPayload,
  IChannelsSaveActionPayload,
  IChannelsRemoveActionPayload,
  IChannelsSocketDataActionPayload,
  IChannelsUnsetActionPayload,
  IChannelsSetActionPayload,
} from './types'

const jsonSchemaValidator = new Ajv()

const jsonApiFormatter = new JsonaService({
  modelPropertiesMapper: new JsonApiModelPropertiesMapper(),
  jsonPropertiesMapper: new JsonApiJsonPropertiesMapper(),
})

const recordFactory = async (data: IChannelRecordFactoryPayload): Promise<IChannel> => {
  const devicesStore = useDevices()

  let device = devicesStore.findById(data.deviceId)

  if (device === null) {
    if (!await devicesStore.get({ id: data.deviceId })) {
      throw new Error('Device for channel couldn\'t be loaded from server')
    }

    device = devicesStore.findById(data.deviceId)

    if (device === null) {
      throw new Error('Device for channel couldn\'t be loaded from store')
    }
  }

  const record: IChannel = {
    id: get(data, 'id', uuid().toString()),
    type: { ...{ entity: 'channel' }, ...data.type },

    draft: get(data, 'draft', false),

    identifier: data.identifier,
    name: get(data, 'name', null),
    comment: get(data, 'comment', null),

    // Relations
    relationshipNames: ['device', 'properties', 'controls'],

    controls: [],
    properties: [],

    device: {
      id: device.id,
      type: device.type,
    },

    // Entity transformers
    get hasComment(): boolean {
      return this.comment !== null && this.comment !== ''
    },
  }

  record.relationshipNames.forEach(relationName => {
    if (relationName === 'properties' || relationName === 'controls') {
      get(data, relationName, [])
        .forEach((relation: any): void => {
          if (get(relation, 'id', null) !== null && get(relation, 'type', null) !== null) {
            (record[relationName] as IPlainRelation[]).push({
              id: get(relation, 'id', null),
              type: get(relation, 'type', null),
            })
          }
        })
    }
  })

  return record
}

const addPropertiesRelations = (channel: IChannel, properties: (IChannelPropertyResponseModel | IPlainRelation)[]): void => {
  const propertiesStore = useChannelProperties()

  properties.forEach(property => {
    if ('identifier' in property) {
      propertiesStore.set({
        data: {
          ...property,
          ...{
            channelId: channel.id,
          },
        }
      })
    }
  })
}

const addControlsRelations = (channel: IChannel, controls: (IChannelControlResponseModel | IPlainRelation)[]): void => {
  const controlsStore = useChannelControls()

  controls.forEach(control => {
    if ('identifier' in control) {
      controlsStore.set({
        data: {
          ...control,
          ...{
            channelId: channel.id,
          },
        }
      })
    }
  })
}

export const useChannels = defineStore<string, IChannelsState, IChannelsGetters, IChannelsActions>('devices_module_channels', {

  state: (): IChannelsState => {
    return {
      semaphore: {
        fetching: {
          items: [],
          item: [],
        },
        creating: [],
        updating: [],
        deleting: [],
      },

      firstLoad: [],

      data: {},
    }
  },

  getters: {

    firstLoadFinished: (state: IChannelsState): ((deviceId: string) => boolean) => {
      return (deviceId => state.firstLoad.includes(deviceId))
    },

    getting: (state: IChannelsState): ((channelId: string) => boolean) => {
      return (channelId => state.semaphore.fetching.item.includes(channelId))
    },

    fetching: (state: IChannelsState): ((deviceId: string | null) => boolean) => {
      return (deviceId => deviceId !== null ? state.semaphore.fetching.items.includes(deviceId) : state.semaphore.fetching.items.length > 0)
    },

    findById: (state: IChannelsState): ((id: string) => IChannel | null) => {
      return (id => {
        const channel = Object.values(state.data).find(channel => channel.id === id)

        return channel ?? null
      })
    },

    findForDevice: (state: IChannelsState): ((deviceId: string) => IChannel[]) => {
      return (deviceId: string): IChannel[] => {
        return Object.values(state.data).filter(channel => channel.device.id === deviceId)
      }
    },

  },

  actions: {

    /**
     * Set record from via other store
     *
     * @param {IChannelsSetActionPayload} payload
     */
    async set(payload: IChannelsSetActionPayload): Promise<IChannel> {
      if (payload.data.id && payload.data.id in this.data) {
        const record = await recordFactory({ ...this.data[payload.data.id], ...payload.data })

        if ('properties' in payload.data && Array.isArray(payload.data.properties)) {
          addPropertiesRelations(record, payload.data.properties)
        }

        if ('controls' in payload.data && Array.isArray(payload.data.controls)) {
          addControlsRelations(record, payload.data.controls)
        }

        return this.data[record.id] = record
      }

      const record = await recordFactory(payload.data)

      if ('properties' in payload.data && Array.isArray(payload.data.properties)) {
        addPropertiesRelations(record, payload.data.properties)
      }

      if ('controls' in payload.data && Array.isArray(payload.data.controls)) {
        addControlsRelations(record, payload.data.controls)
      }

      return this.data[record.id] = record
    },

    /**
     * Remove records for given relation or record by given identifier
     *
     * @param {IChannelsUnsetActionPayload} payload
     */
    unset(payload: IChannelsUnsetActionPayload): void {
      if (typeof payload.device !== 'undefined') {
        Object.keys(this.data)
          .forEach(id => {
            if (id in this.data && this.data[id].device.id === payload.device?.id) {
              delete this.data[id]
            }
          })

        return

      } else if (typeof payload.id !== 'undefined') {
        if (payload.id in this.data) {
          delete this.data[payload.id]
        }

        return
      }

      throw new Error('You have to provide at least device or channel id')
    },

    /**
     * Get one record from server
     *
     * @param {IChannelsGetActionPayload} payload
     */
    async get(payload: IChannelsGetActionPayload): Promise<boolean> {
      if (this.semaphore.fetching.item.includes(payload.id)) {
        return false
      }

      this.semaphore.fetching.item.push(payload.id)

      try {
        const channelResponse = await axios.get<IChannelResponseJson>(
          `/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.device.id}/channels/${payload.id}?include=properties,controls`,
        )

        const channelResponseModel = jsonApiFormatter.deserialize(channelResponse.data) as IChannelResponseModel

        this.data[channelResponseModel.id] = await recordFactory({
          ...channelResponseModel,
          ...{ deviceId: channelResponseModel.device.id },
        })

        addPropertiesRelations(this.data[channelResponseModel.id], channelResponseModel.properties)
        addControlsRelations(this.data[channelResponseModel.id], channelResponseModel.controls)
      } catch (e: any) {
        throw new ApiError(
          'devices-module.channels.get.failed',
          e,
          'Fetching channel failed.',
        )
      } finally {
        this.semaphore.fetching.item = this.semaphore.fetching.item.filter(item => item !== payload.id)
      }

      return true
    },

    /**
     * Fetch all records from server
     *
     * @param {IChannelsFetchActionPayload} payload
     */
    async fetch(payload: IChannelsFetchActionPayload): Promise<boolean> {
      if (this.semaphore.fetching.items.includes(payload.device.id)) {
        return false
      }

      this.semaphore.fetching.items.push(payload.device.id)

      try {
        const channelsResponse = await axios.get<IChannelsResponseJson>(
          `/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.device.id}/channels?include=properties,controls`,
        )

        const channelsResponseModel = jsonApiFormatter.deserialize(channelsResponse.data) as IChannelResponseModel[]

        for (const channel of channelsResponseModel) {
          this.data[channel.id] = await recordFactory({
            ...channel,
            ...{ deviceId: channel.device.id },
          })

          addPropertiesRelations(this.data[channel.id], channel.properties)
          addControlsRelations(this.data[channel.id], channel.controls)
        }

        this.firstLoad.push(payload.device.id)
      } catch (e: any) {
        throw new ApiError(
          'devices-module.channels.fetch.failed',
          e,
          'Fetching channels failed.',
        )
      } finally {
        this.semaphore.fetching.items = this.semaphore.fetching.items.filter(item => item !== payload.device.id)
      }

      return true
    },

    /**
     * Add new record
     *
     * @param {IChannelsAddActionPayload} payload
     */
    async add(payload: IChannelsAddActionPayload): Promise<IChannel> {
      const newChannel = await recordFactory({
        ...payload.data,
        ...{
            id: payload?.id,
            type: payload?.type,
            draft: payload?.draft,
            deviceId: payload.device.id,
        },
      })

      this.semaphore.creating.push(newChannel.id)

      this.data[newChannel.id] = newChannel

      if (newChannel.draft) {
        this.semaphore.creating = this.semaphore.creating.filter(item => item !== newChannel.id)

        return newChannel
      } else {
        try {
          const createdChannel = await axios.post<IChannelResponseJson>(
            `/${ModulePrefix.MODULE_DEVICES}/v1/devices/${payload.device.id}/channels?include=properties,controls`,
            jsonApiFormatter.serialize({
              stuff: newChannel,
            }),
          )

          const createdChannelModel = jsonApiFormatter.deserialize(createdChannel.data) as IChannelResponseModel

          this.data[createdChannelModel.id] = await recordFactory({
            ...createdChannelModel,
            ...{ deviceId: createdChannelModel.device.id },
          })

          addPropertiesRelations(this.data[createdChannelModel.id], createdChannelModel.properties)
          addControlsRelations(this.data[createdChannelModel.id], createdChannelModel.controls)

          return this.data[createdChannelModel.id]
        } catch (e: any) {
          // Entity could not be created on api, we have to remove it from database
          delete this.data[newChannel.id]

          throw new ApiError(
            'devices-module.channels.create.failed',
            e,
            'Create new channel failed.',
          )
        } finally {
          this.semaphore.creating = this.semaphore.creating.filter(item => item !== newChannel.id)
        }
      }
    },

    /**
     * Edit existing record
     *
     * @param {IChannelsEditActionPayload} payload
     */
    async edit(payload: IChannelsEditActionPayload): Promise<IChannel> {
      if (this.semaphore.updating.includes(payload.id)) {
        throw new Error('devices-module.channels.update.inProgress')
      }

      if (!Object.keys(this.data).includes(payload.id)) {
        throw new Error('devices-module.channels.update.failed')
      }

      this.semaphore.updating.push(payload.id)

      // Get record stored in database
      const existingRecord = this.data[payload.id]
      // Update with new values
      const updatedRecord = {...existingRecord, ...payload.data} as IChannel

      this.data[payload.id] = updatedRecord

      if (updatedRecord.draft) {
        this.semaphore.updating = this.semaphore.updating.filter(item => item !== payload.id)

        return this.data[payload.id]
      } else {
        try {
          const updatedChannel = await axios.patch<IChannelResponseJson>(
            `/${ModulePrefix.MODULE_DEVICES}/v1/devices/${updatedRecord.device.id}/channels/${updatedRecord.id}?include=properties,controls`,
            jsonApiFormatter.serialize({
              stuff: updatedRecord,
            }),
          )

          const updatedChannelModel = jsonApiFormatter.deserialize(updatedChannel.data) as IChannelResponseModel

          this.data[updatedChannelModel.id] = await recordFactory({
            ...updatedChannelModel,
            ...{ deviceId: updatedChannelModel.device.id },
          })

          addPropertiesRelations(this.data[updatedChannelModel.id], updatedChannelModel.properties)
          addControlsRelations(this.data[updatedChannelModel.id], updatedChannelModel.controls)

          return this.data[updatedChannelModel.id]
        } catch (e: any) {
          const devicesStore = useDevices()

          const device = devicesStore.findById(updatedRecord.device.id)

          if (device !== null) {
            // Updating entity on api failed, we need to refresh entity
            await this.get({ device, id: payload.id })
          }

          throw new ApiError(
            'devices-module.channels.update.failed',
            e,
            'Edit channel failed.',
          )
        } finally {
          this.semaphore.updating = this.semaphore.updating.filter(item => item !== payload.id)
        }
      }
    },

    /**
     * Save draft record on server
     *
     * @param {IChannelsSaveActionPayload} payload
     */
    async save(payload: IChannelsSaveActionPayload): Promise<IChannel> {
      if (this.semaphore.updating.includes(payload.id)) {
        throw new Error('devices-module.channels.save.inProgress')
      }

      if (!Object.keys(this.data).includes(payload.id)) {
        throw new Error('devices-module.channels.save.failed')
      }

      this.semaphore.updating.push(payload.id)

      const recordToSave = this.data[payload.id]

      try {
        const savedChannel = await axios.post<IChannelResponseJson>(
          `/${ModulePrefix.MODULE_DEVICES}/v1/devices/${recordToSave.device.id}/channels?include=properties,controls`,
          jsonApiFormatter.serialize({
            stuff: recordToSave,
          }),
        )

        const savedChannelModel = jsonApiFormatter.deserialize(savedChannel.data) as IChannelResponseModel

        this.data[savedChannelModel.id] = await recordFactory({
          ...savedChannelModel,
          ...{ deviceId: savedChannelModel.device.id },
        })

        addPropertiesRelations(this.data[savedChannelModel.id], savedChannelModel.properties)
        addControlsRelations(this.data[savedChannelModel.id], savedChannelModel.controls)

        return this.data[savedChannelModel.id]
      } catch (e: any) {
        throw new ApiError(
          'devices-module.channels.save.failed',
          e,
          'Save draft channel failed.',
        )
      } finally {
        this.semaphore.updating = this.semaphore.updating.filter(item => item !== payload.id)
      }
    },

    /**
     * Remove existing record from store and server
     *
     * @param {IChannelsRemoveActionPayload} payload
     */
    async remove(payload: IChannelsRemoveActionPayload): Promise<boolean> {
      if (this.semaphore.deleting.includes(payload.id)) {
        throw new Error('devices-module.channels.delete.inProgress')
      }

      if (!Object.keys(this.data).includes(payload.id)) {
        throw new Error('devices-module.channels.delete.failed')
      }

      this.semaphore.deleting.push(payload.id)

      const recordToDelete = this.data[payload.id]

      delete this.data[payload.id]

      if (recordToDelete.draft) {
        this.semaphore.deleting = this.semaphore.deleting.filter(item => item !== payload.id)

      } else {
        try {
          await axios.delete(
            `/${ModulePrefix.MODULE_DEVICES}/v1/devices/${recordToDelete.device.id}/channels/${recordToDelete.id}`,
          )
        } catch (e: any) {
          const devicesStore = useDevices()

          const device = devicesStore.findById(recordToDelete.device.id)

          if (device !== null) {
            // Deleting entity on api failed, we need to refresh entity
            await this.get({ device, id: payload.id })
          }

          throw new ApiError(
            'devices-module.channels.delete.failed',
            e,
            'Delete channel failed.',
          )
        } finally {
          this.semaphore.deleting = this.semaphore.deleting.filter(item => item !== payload.id)
        }
      }

      return true
    },

    /**
     * Receive data from sockets
     *
     * @param {IChannelsSocketDataActionPayload} payload
     */
    async socketData(payload: IChannelsSocketDataActionPayload): Promise<boolean> {
      if (
        ![
          RoutingKeys.CHANNEL_ENTITY_REPORTED,
          RoutingKeys.CHANNEL_ENTITY_CREATED,
          RoutingKeys.CHANNEL_ENTITY_UPDATED,
          RoutingKeys.CHANNEL_ENTITY_DELETED,
        ].includes(payload.routingKey as RoutingKeys)
      ) {
        return false
      }

      const body: ExchangeEntity = JSON.parse(payload.data)

      const isValid = jsonSchemaValidator.compile<ExchangeEntity>(exchangeEntitySchema)

      if (!isValid(body)) {
        return false
      }

      if (payload.routingKey === RoutingKeys.CHANNEL_ENTITY_DELETED) {
        if (body.id in this.data) {
          const recordToDelete = this.data[body.id]

          delete this.data[body.id]

          const channelPropertiesStore = useChannelProperties()
          const channelControlsStore = useChannelControls()

          channelPropertiesStore.unset({ channel: recordToDelete })
          channelControlsStore.unset({ channel: recordToDelete })
        }

      } else {
        if (payload.routingKey === RoutingKeys.CHANNEL_ENTITY_UPDATED && this.semaphore.updating.includes(body.id)) {
          return true
        }

        if (body.id in this.data) {
          this.data[body.id] = await recordFactory({
            ...this.data[body.id],
            ...{
              name: body.name,
              comment: body.comment,
              deviceId: body.device,
            },
          })
        } else {
          const devicesStore = useDevices()

          const device = devicesStore.findById(body.device)

          if (device !== null) {
            this.get({
              device,
              id: body.id,
            })
          } else {
            devicesStore.get({ id: body.device, withChannels: true })
          }
        }
      }

      return true
    },

  },

})
