import { computed, inject } from 'vue'

import { IWebSocketResult, key, RpCallResponse } from '@/entry'

export function useWsExchangeClient<T>(): IWebSocketResult<T> {
  const wampClient = inject(key)

  if (wampClient === undefined) {
    throw new Error('WAMP client is not installed')
  }

  return {
    status: computed<boolean>((): boolean => wampClient.isConnected.value),
    open: (): void => wampClient.open(),
    close: (): void => wampClient.close(),
    call: (...args): Promise<RpCallResponse<T>> => wampClient.call(...args),
    client: wampClient,
  }
}
