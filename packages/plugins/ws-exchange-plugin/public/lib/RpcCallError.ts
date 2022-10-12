import { IRpcCallError } from '@/types/ws-exchange-plugin'

class RpcCallError extends Error implements IRpcCallError {
  public topic: string
  public message: string

  public details: string | any[] | null

  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  constructor(topic: string, message: string, ...params: any) {
    // Pass remaining arguments (including vendor specific ones) to parent constructor
    super(...params)

    // Maintains proper stack trace for where our error was thrown (only available on V8)
    if (Error.captureStackTrace) {
      Error.captureStackTrace(this, RpcCallError)
    }

    // Custom debugging information
    this.topic = topic
    this.message = message
    this.details = null
  }
}

export default RpcCallError
