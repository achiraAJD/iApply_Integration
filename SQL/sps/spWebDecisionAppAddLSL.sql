USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebDecisionAppAddLSL]    Script Date: 1/07/2022 11:50:53 AM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO


/*
+--------------------------------------------------------------------------------------------
| FUNCTION:  
| HISTORY:
| DATE			WHO      					DESCRIPTION OF CHANGE
| ---------------------------------------------------------------------------------
| 25/09/2019 	NIDDD						Created 
+--------------------------------------------------------------------------------------------*/

ALTER PROCEDURE [dbo].[spWebDecisionAppAddLSL]
	@LSL_LIC_ID numeric(18),
	@LSL_JSON varchar(max),
	@LSL_User varchar(15),
	@LSL_LSLC_ID numeric(18),
	@LSL_AS_ID numeric(18)
AS

SET NOCOUNT ON;

BEGIN
	INSERT INTO LicenceSystemLicences (LSL_LIC_ID, LSL_CB_ID_Status, LSL_Date, LSL_User, LSL_JSON, LSL_Capacity, LSL_LSLC_ID, CreationDateTime, CreationUser, LastUpdateDateTime, LastUpdateUser, LSL_AS_ID)
	values (@LSL_LIC_ID, 1670, getdate(), @LSL_User, @LSL_JSON, null, @LSL_LSLC_ID, getdate(), @LSL_User, getdate(), @LSL_User, @LSL_AS_ID)

	select SCOPE_IDENTITY() as LSL_ID
END


--grant execute on [spWebDecisionAppAddLSL] to webuser