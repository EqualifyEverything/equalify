export const unformatId = (value: string) => 
  value.length === 32 
    ? value.substr(0, 8) + '-' + value.substr(8, 4) + '-' + value.substr(12, 4) + '-' + value.substr(16, 4) + '-' + value.substr(20, 12)
    : value
