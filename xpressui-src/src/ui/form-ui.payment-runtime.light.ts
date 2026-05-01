type TPaymentRuntimeHost = any;

export function createPaymentRuntime(_host: TPaymentRuntimeHost) {
  return {
    initField(_fieldConfig: { name: string }) {},
    async confirmPaymentsIfNeeded() {
      return { ok: true as const };
    },
  };
}
