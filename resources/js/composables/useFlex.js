export const useFlex = (options = {}) => {
 


   // Get all options with defaults

   const {
    direction = 'row',
    justify = 'start',
    align = 'stretch',
    gap = 'none',
    wrap = 'nowrap',
    inline = false,
   } = options
 
  // Gap mapping - makes it easier to use
  const gapMap = {
    'none': 'gap-0',
    'xs': 'gap-1',
    'sm': 'gap-2',
    'md': 'gap-4',
    'lg': 'gap-6',
    'xl': 'gap-8',
    '2xl': 'gap-12',
  }
  
  // Build classes array
  const classes = [
    inline ? 'inline-flex': 'flex',
    'w-full',
    'h-full',
    `flex-${wrap}`,
    `flex-${direction}`,       // flex-row, flex-col
    `justify-${justify}`,       // justify-start, justify-center
    `items-${align}`,           // items-stretch, items-center
  ]
  
  // Add gap if specified
  if (gap !== 'none') {
    classes.push(gapMap[gap] || gap)
  }
  
  // Return joined string
  return classes.join(' ')
}

//PRESET FOR QUICK USE

export const flex = {
    center: () => useFlex({justify: 'center', align: 'center' }),
    centerCol: () => useFlex({direction: 'col', justify: 'center', align: 'center'}),
    stack: (gap = 'md') => useFlex({ direction: 'col', gap}),
    row: (gap = 'md') => useFlex({direction: 'row', gap}),
    between: ()=> useFlex({justify: 'between', align: 'center'}),
    wrap: (gap = 'md') => useFlex({wrap: 'wrap', gap}),
}