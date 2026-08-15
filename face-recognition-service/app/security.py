from fastapi import Header,HTTPException
from hmac import compare_digest
from .config import TOKEN
def authorize(authorization:str|None=Header(None)):
 expected=f'Bearer {TOKEN}'
 if not TOKEN or not authorization or not compare_digest(authorization,expected): raise HTTPException(401,detail={'code':'INVALID_AUTH_TOKEN','message':'Token layanan tidak valid.'})
