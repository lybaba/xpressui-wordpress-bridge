import TFieldConfig from "../common/TFieldConfig";
import type { TImageGalleryItem, TProductCartItem, TProductListItem } from "./form-ui.types";

function getCatalogDisplayName(choice: any, fallback: string): string {
  return String(
    choice?.label
      || choice?.title
      || choice?.adminLabel
      || choice?.name
      || choice?.value
      || choice?.id
      || fallback,
  );
}

export function getProductListCatalog(fieldConfig: TFieldConfig): TProductListItem[] {
  const source = Array.isArray(fieldConfig.choices) ? fieldConfig.choices : [];
  return source
    .slice(0, 20)
    .map((choice, index) => {
      const id = String((choice as any).value || (choice as any).id || `product_${index + 1}`);
      const name = getCatalogDisplayName(choice, id);
      const salePriceRaw = (choice as any).sale_price ?? (choice as any).salePrice;
      const discountPriceRaw = (choice as any).discount_price ?? (choice as any).discountPrice;
      const sale_price = salePriceRaw === undefined || salePriceRaw === null ? null : Number(salePriceRaw);
      const discount_price =
        discountPriceRaw === undefined || discountPriceRaw === null ? null : Number(discountPriceRaw);
      const image_thumbnail = String((choice as any).image_thumbnail || (choice as any).imageThumbnail || "");
      const photosSource = (choice as any).photos_full ?? (choice as any).photosFull;
      const photos_full = Array.isArray(photosSource)
        ? photosSource.map((entry: any) => String(entry)).filter(Boolean)
        : [];
      const image_medium = String(
        photos_full[0] || (choice as any).image_medium || (choice as any).imageMedium || image_thumbnail,
      );
      const maxNumOfChoicesRaw = Number((choice as any).maxNumOfChoices);

      return {
        id,
        name,
        sale_price: Number.isFinite(sale_price as number) ? sale_price : null,
        discount_price: Number.isFinite(discount_price as number) ? discount_price : null,
        image_thumbnail,
        image_medium,
        photos_full,
        maxNumOfChoices: Number.isFinite(maxNumOfChoicesRaw) && maxNumOfChoicesRaw > 0
          ? Math.round(maxNumOfChoicesRaw)
          : undefined,
      };
    });
}

export function getImageGalleryCatalog(fieldConfig: TFieldConfig): TImageGalleryItem[] {
  const source = Array.isArray(fieldConfig.choices) ? fieldConfig.choices : [];
  return source
    .slice(0, 20)
    .map((choice, index) => {
      const id = String((choice as any).value || (choice as any).id || `image_${index + 1}`);
      const name = getCatalogDisplayName(choice, id);
      const salePriceRaw = (choice as any).sale_price ?? (choice as any).salePrice;
      const discountPriceRaw = (choice as any).discount_price ?? (choice as any).discountPrice;
      const sale_price = salePriceRaw === undefined || salePriceRaw === null ? null : Number(salePriceRaw);
      const discount_price =
        discountPriceRaw === undefined || discountPriceRaw === null ? null : Number(discountPriceRaw);
      const image_thumbnail = String((choice as any).image_thumbnail || (choice as any).imageThumbnail || "");
      const photosSource = (choice as any).photos_full ?? (choice as any).photosFull;
      const photos_full = Array.isArray(photosSource)
        ? photosSource.map((entry: any) => String(entry)).filter(Boolean)
        : [];
      const image_medium = String(
        photos_full[0] || (choice as any).image_medium || (choice as any).imageMedium || image_thumbnail,
      );
      const maxNumOfChoicesRaw = Number((choice as any).maxNumOfChoices);

      return {
        id,
        name,
        sale_price: Number.isFinite(sale_price as number) ? sale_price : null,
        discount_price: Number.isFinite(discount_price as number) ? discount_price : null,
        image_thumbnail,
        image_medium,
        photos_full,
        maxNumOfChoices: Number.isFinite(maxNumOfChoicesRaw) && maxNumOfChoicesRaw > 0
          ? Math.round(maxNumOfChoicesRaw)
          : undefined,
      };
    });
}

export function getProductCartItems(value: any): TProductCartItem[] {
  if (!Array.isArray(value)) {
    return [];
  }

  return value
    .filter((entry) => entry && typeof entry === "object")
    .map((entry) => {
      const quantityRaw = Number((entry as any).quantity || 1);
      return {
        id: String((entry as any).id || (entry as any).value || ""),
        name: getCatalogDisplayName(entry, ""),
        sale_price:
          (entry as any).sale_price === undefined || (entry as any).sale_price === null
            ? null
            : Number((entry as any).sale_price),
        discount_price:
          (entry as any).discount_price === undefined || (entry as any).discount_price === null
            ? null
            : Number((entry as any).discount_price),
        image_thumbnail: String((entry as any).image_thumbnail || ""),
        image_medium: String((entry as any).image_medium || ""),
        photos_full: Array.isArray((entry as any).photos_full)
          ? (entry as any).photos_full.map((photo: any) => String(photo)).filter(Boolean)
          : [],
        maxNumOfChoices: Number.isFinite(Number((entry as any).maxNumOfChoices)) && Number((entry as any).maxNumOfChoices) > 0
          ? Math.round(Number((entry as any).maxNumOfChoices))
          : undefined,
        quantity: Number.isFinite(quantityRaw) && quantityRaw > 0 ? Math.round(quantityRaw) : 1,
      };
    })
    .filter((entry) => Boolean(entry.id));
}

export function getImageGallerySelectionItems(value: any): TImageGalleryItem[] {
  if (!Array.isArray(value)) {
    return [];
  }

  return value
    .filter((entry) => entry && typeof entry === "object")
    .map((entry, index) => ({
      id: String((entry as any).id || (entry as any).value || `image_${index + 1}`),
      name: getCatalogDisplayName(entry, (entry as any).id || ""),
      sale_price:
        (entry as any).sale_price === undefined || (entry as any).sale_price === null
          ? null
          : Number((entry as any).sale_price),
      discount_price:
        (entry as any).discount_price === undefined || (entry as any).discount_price === null
          ? null
          : Number((entry as any).discount_price),
      image_thumbnail: String((entry as any).image_thumbnail || ""),
      image_medium: String((entry as any).image_medium || ""),
      photos_full: Array.isArray((entry as any).photos_full)
        ? (entry as any).photos_full.map((photo: any) => String(photo)).filter(Boolean)
        : [],
      maxNumOfChoices: Number.isFinite(Number((entry as any).maxNumOfChoices)) && Number((entry as any).maxNumOfChoices) > 0
        ? Math.round(Number((entry as any).maxNumOfChoices))
        : undefined,
    }))
    .filter((entry) => Boolean(entry.id));
}

export function getProductCartTotal(cartItems: TProductCartItem[]): number {
  return cartItems.reduce((sum, item) => {
    const unitPrice = item.discount_price ?? item.sale_price ?? 0;
    return sum + (Number.isFinite(unitPrice) ? unitPrice : 0) * item.quantity;
  }, 0);
}
