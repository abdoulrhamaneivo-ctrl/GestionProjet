files = [
    'student-hub-php/public/lib/font/helvetica.json',
    'student-hub-php/public/lib/font/helveticab.json',
    'student-hub-php/public/lib/font/helveticai.json',
    'student-hub-php/public/lib/font/helveticabi.json',
]
for p in files:
    c = open(p, 'r', encoding='utf-8').read()
    c = c.replace('"type":"core"', '"type":"Core"')
    open(p, 'w', encoding='utf-8').write(c)
print('done')
