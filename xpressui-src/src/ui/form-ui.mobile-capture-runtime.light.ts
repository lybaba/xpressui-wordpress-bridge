type TMobileCaptureHost = any;

export function createMobileCaptureRuntime(_host: TMobileCaptureHost) {
  return {
    initField(_fieldConfig: { name: string; type: string }) {},
  };
}
