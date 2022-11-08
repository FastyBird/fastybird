import {FbLayoutHeaderHeadingAlign} from "@/types";

export interface IFbLayoutHeaderHeadingProps {
    heading: string;
    subHeading: string | null;
    align: FbLayoutHeaderHeadingAlign;
    teleport: boolean;
}
