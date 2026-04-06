import TFieldConfig from "./TFieldConfig";

type TSchema = {
    ajvSchema: any;
    fieldMap: Record<string, TFieldConfig>;
}

export default TSchema;