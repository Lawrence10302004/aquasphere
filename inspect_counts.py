import re
from pathlib import Path
text = Path('cart.html').read_text(encoding='utf-8')
start = text.index('const philippineLocations =')
end = text.index('// Initialize location dropdowns', start)
block = text[start:end]
block = block.replace('const philippineLocations =','').strip()
if block.endswith(';'): block = block[:-1]
block = block.replace('–','-')
# quote bare keys
block = re.sub(r'(\s)([A-Za-z_][\w]*)\s*:', r"\1'\2':", block)
block_py = block.replace(': true', ': True').replace(': false', ': False')

data = eval(block_py)
laguna = data['CALABARZON']['provinces']['Laguna']['cities']
print('Total barangays:', sum(len(v['barangays']) for v in laguna.values()))
print('San Pablo count:', len(laguna['San Pablo']['barangays']))
