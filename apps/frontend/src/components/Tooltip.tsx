import * as RadixTooltip from '@radix-ui/react-tooltip';
import { MdInfoOutline } from "react-icons/md";

export const Tooltip = ({ trigger = <MdInfoOutline className='text-xs inline-flex mb-[4px] ml-[2px]' />, content }) => {
    return (
        <RadixTooltip.Provider>
            <RadixTooltip.Root>
                <RadixTooltip.Trigger asChild>
                    {trigger}
                </RadixTooltip.Trigger>
                <RadixTooltip.Portal>
                    <RadixTooltip.Content
                        className="bg-background border-text border-[1px] rounded-xl p-4 min-w-[300px] max-w-[400px] z-50"
                        sideOffset={0}
                    >
                        {content}
                    </RadixTooltip.Content>
                </RadixTooltip.Portal>
            </RadixTooltip.Root>
        </RadixTooltip.Provider>
    );
};