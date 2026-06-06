import { TMediaInfo } from "./TMediaFile";

type TChoice = {
    value: string;
    label: string;
    id?: string;
    name?: string;
    desc?: string;
    maxNumOfChoices?: number;
    mediaId?: string;
    mediaInfo?: TMediaInfo;
    mediaInfoList?: TMediaInfo[];
    permalink?: string;
    regularPrice?: number;
    salePrice?: number;
    sale_price?: number;
    discountPrice?: number;
    discount_price?: number;
    imageThumbnail?: string;
    image_thumbnail?: string;
    imageMedium?: string;
    image_medium?: string;
    photosFull?: string[];
    photos_full?: string[];
    disabled?: boolean;
}

export default TChoice;
