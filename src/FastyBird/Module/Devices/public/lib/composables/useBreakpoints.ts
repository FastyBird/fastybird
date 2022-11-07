import { computed } from 'vue'

import {
  breakpointsBootstrapV5,
  useBreakpoints as vueUseBreakpoints,
} from '@vueuse/core'

const breakpoints = vueUseBreakpoints(breakpointsBootstrapV5)

export function useBreakpoints() {
  const isExtraSmallDevice = computed<boolean>((): boolean => !breakpoints.md.value)
  const isSmallDevice = computed<boolean>((): boolean => !breakpoints.lg.value)

  return {
    isExtraSmallDevice,
    isSmallDevice,
  }
}
