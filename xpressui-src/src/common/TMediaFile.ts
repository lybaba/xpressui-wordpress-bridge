
export type TMediaFileMetadata = {
    width?: number;
    height?: number;
    publicUrl: string;
    label?: string;
};

export enum MediaSizeType {
    Small,
    Thumb,
    Medium,
    Large
}

export type TMediaInfo = {
    publicUrl?: string;
    label?: string;
    small?: TMediaFileMetadata;
    thumb?: TMediaFileMetadata;
    medium?: TMediaFileMetadata; 
    large?: TMediaFileMetadata; 
}

type TMediaFile = TMediaInfo & {
    id: string;
    label: string;
    desc?: string;
}


export default TMediaFile;
