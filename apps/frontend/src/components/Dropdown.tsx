import * as DropdownMenu from '@radix-ui/react-dropdown-menu';

export const Dropdown = ({ options, children }) => {
    return (
        <DropdownMenu.Root>
            <DropdownMenu.Trigger className='outline-none'>{children}</DropdownMenu.Trigger>
            <DropdownMenu.Portal>
                <DropdownMenu.Content className="min-w-[120px] text-text bg-card rounded-lg p-1 z-40 select-none shadow-xl border-[1px] border-text">
                    {options.map(({ label, action }, index) =>
                        <DropdownMenu.Item key={index} onClick={action} className='hover:opacity-50 px-2 py-1 rounded-lg flex flex-row items-center gap-1'>
                            {label}
                        </DropdownMenu.Item>)}
                </DropdownMenu.Content>
            </DropdownMenu.Portal>
        </DropdownMenu.Root>
    );
};