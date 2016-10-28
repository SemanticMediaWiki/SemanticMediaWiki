local smw = {}
local php

function smw.setupInterface( options )
    -- Remove setup function
    smw.setupInterface = nil

    -- Copy the PHP callbacks to a local variable, and remove the global
    php = mw_interface
    mw_interface = nil

    -- Do any other setup here
	-- options is an emtpy array. see method "register" in include\LuaLibrary.php

    -- Install into the mw global
    mw = mw or {}
    mw.ext = mw.ext or {}
    mw.ext.smw = smw

    -- Indicate that we're loaded
    package.loaded['mw.ext.smw'] = smw
end

function smw.ask( parameters )
	return php.ask( parameters )
end

function smw.getPropertyCanonicalLabel( property )
	if property and type( property ) == 'string' then
		return php.getPropertyCanonicalLabel( property )
	else
		return nil
	end
end

function smw.getPropertyLabel( property )
	if property and type( property ) == 'string' then
		return php.getPropertyLabel( property )
	else
		return nil
	end
end

function smw.getPropertyType( property )
	if property and type( property ) == 'string' then
		return php.getPropertyType( property )
	else
		return nil
	end
end

function smw.info( text, icon )
	return php.info( text, icon )
end

function smw.set( parameters )
	return php.set( parameters )
end

function smw.subobject( parameters, subobjectId )
	return php.subobject( parameters, subobjectId )
end

return smw
