import type { Module } from '@nuxt/types'
import { NuxtRouteConfig } from '@nuxt/types/config/router'
import * as path from 'path'

const extendRoutes = (routes: NuxtRouteConfig[]): void => {
  routes.push({
    name: 'devices',
    path: '/devices',
    component: path.resolve(__dirname, './../lib/ui/pages/index.vue'),
  })

  routes.push({
    name: 'device-detail',
    path: '/devices/:id',
    component: path.resolve(__dirname, './../lib/ui/pages/_id/index.vue'),
  })
}

export default (function nuxtUserAgent() {
  this.extendRoutes(extendRoutes)

  this.addPlugin({
    src: path.resolve(__dirname, 'plugin.ts'),
    fileName: 'devices-module.js',
  })
}) as Module

import meta from '../../package.json'

export {
  meta,
}
