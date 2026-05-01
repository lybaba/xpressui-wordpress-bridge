type TSignatureRuntimeHost = any;

export function createSignatureRuntime(_host: TSignatureRuntimeHost) {
  return {
    initField(_fieldConfig: { name: string }) {},
  };
}
